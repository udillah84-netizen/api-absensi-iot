<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\AttendanceScanLog;
use App\Models\AttendanceSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IotAttendanceController extends Controller
{
    /**
     * Endpoint Utama: Memproses Tap RFID / Barcode Scanner dari ESP32
     * POST /api/v1/iot/scan
     */
    public function scan(Request $request): JsonResponse
    {
        // Kompatibilitas jika mesin mengirim parameter 'kode' alih-alih 'code'
        if ($request->has('kode') && !$request->has('code')) {
            $request->merge(['code' => $request->input('kode')]);
        }

        $request->validate([
            'code'       => 'required|string|max:128',
            'device_id'  => 'nullable|string|max:50',
            'scanned_at' => 'nullable|string',
            'jenis'      => 'nullable|string|in:RFID,BARCODE,rfid,barcode',
        ]);

        $code = trim($request->input('code'));
        $deviceId = $request->input('device_id') ?? $request->input('iot_device_id', 'DEV-UNKNOWN');
        $jenis = strtoupper($request->input('jenis', 'RFID'));

        // Parse waktu scan (mendukung sinkronisasi offline timestamp dari RTC ESP32)
        $scannedAt = $request->filled('scanned_at')
            ? Carbon::parse($request->input('scanned_at'))
            : now();

        $logData = [
            'device_id'  => $deviceId,
            'code'       => $code,
            'jenis'      => $jenis,
            'status'     => 'failed',
            'message'    => null,
            'scanned_at' => $scannedAt,
        ];

        // 1. Update status online perangkat
        if ($deviceId) {
            $device = AttendanceDevice::where('device_id', $deviceId)->first();
            if ($device) {
                $device->updateHeartbeat($request->ip());
            }
        }

        // 2. Cari Data Pengguna di Database (fleksibel: RFID, Barcode, NISN/NIP, ID)
        $user = User::where('rfid_uid', $code)
            ->orWhere('barcode_uid', $code)
            ->orWhere('identifier_number', $code)
            ->orWhere('id', $code)
            ->first();

        // Skenario 1: Kartu / Barcode Tidak Terdaftar (404)
        if (!$user) {
            $logData['message'] = 'Kartu / Kode tidak terdaftar';
            $this->saveLog($logData);

            return response()->json([
                'success' => false,
                'status'  => 'Gagal',
                'nama'    => null,
                'message' => 'Kartu Tidak Terdaftar',
            ], 404);
        }

        // Skenario 2: Akun Dinonaktifkan (403)
        if (isset($user->is_active) && !$user->is_active) {
            $logData['user_id'] = $user->id;
            $logData['tipe_user'] = $user->role ?? 'user';
            $logData['message'] = 'Akun nonaktif';
            $this->saveLog($logData);

            return response()->json([
                'success' => false,
                'status'  => 'Ditolak',
                'nama'    => $this->formatLcdName($user->name),
                'message' => 'Akun Pengguna Nonaktif',
            ], 403);
        }

        $logData['user_id'] = $user->id;
        $logData['tipe_user'] = $user->role ?? 'siswa';

        // 3. Ambil Konfigurasi Jam Presensi
        $setting = AttendanceSetting::getForRole($user->role ?? 'siswa');
        $jamMasukSetting = $setting->jam_masuk ?? '07:00:00';
        $batasTerlambat  = $setting->batas_terlambat ?? '07:30:00';
        $jamPulangSetting = $setting->jam_pulang ?? '14:00:00';

        $tanggal = $scannedAt->toDateString();
        $jamSekarang = $scannedAt->format('H:i:s');
        $jamDisplay = $scannedAt->format('H:i');
        $namaLcd = $this->formatLcdName($user->name);
        $departemen = $user->department ?? ($user->kelas->nama_kelas ?? '-');

        // 4. Cek Transaksi Presensi Hari Ini
        $attendance = Attendance::where('user_id', $user->id)
            ->where('tanggal', $tanggal)
            ->first();

        $dupWindow = config('iot.duplicate_window_seconds', 30);

        // Anti-Duplicate Tap (Jeda < 30 detik dari scan terakhir)
        if ($attendance && $attendance->updated_at && $attendance->updated_at->diffInSeconds($scannedAt) < $dupWindow) {
            $logData['status'] = 'success';
            $logData['message'] = 'Duplicate tap ignored (Anti-Spam)';
            $this->saveLog($logData);

            return response()->json([
                'success' => true,
                'status'  => $attendance->status,
                'nama'    => $namaLcd,
                'kelas'   => $departemen,
                'jam'     => $jamDisplay,
                'message' => 'Sudah Absen Barusan',
            ]);
        }

        // Skenario 3: Absen Pulang (Jika sudah absen masuk dan waktu sudah mencapai jam pulang)
        if ($attendance && empty($attendance->jam_pulang) && $scannedAt->format('H:i:s') >= $jamPulangSetting) {
            $attendance->update([
                'jam_pulang'       => $jamSekarang,
                'device_id_pulang' => $deviceId,
                'metode_pulang'    => $jenis,
            ]);

            $logData['status'] = 'success';
            $logData['message'] = 'Absen Pulang Berhasil';
            $this->saveLog($logData);

            return response()->json([
                'success' => true,
                'status'  => 'Pulang',
                'nama'    => $namaLcd,
                'kelas'   => $departemen,
                'jam'     => $jamDisplay,
                'message' => 'Absensi Pulang Berhasil',
            ]);
        }

        // Skenario 4: Sudah Pernah Absen Masuk Hari Ini & Belum Waktunya Pulang
        if ($attendance && !empty($attendance->jam_masuk)) {
            $logData['status'] = 'success';
            $logData['message'] = 'Sudah absen masuk sebelumnya';
            $this->saveLog($logData);

            return response()->json([
                'success' => true,
                'status'  => $attendance->status,
                'nama'    => $namaLcd,
                'kelas'   => $departemen,
                'jam'     => substr($attendance->jam_masuk, 0, 5),
                'message' => 'Sudah Absen Hari Ini',
            ]);
        }

        // Skenario 5: Absen Masuk Pertama Kali Hari Ini (Hadir atau Telat)
        $statusMasuk = ($scannedAt->format('H:i:s') <= $batasTerlambat) ? 'Hadir' : 'Telat';

        Attendance::updateOrCreate(
            ['user_id' => $user->id, 'tanggal' => $tanggal],
            [
                'jam_masuk'        => $jamSekarang,
                'status'           => $statusMasuk,
                'device_id_masuk'  => $deviceId,
                'metode_masuk'     => $jenis,
            ]
        );

        $logData['status'] = 'success';
        $logData['message'] = 'Absen Masuk: ' . $statusMasuk;
        $this->saveLog($logData);

        return response()->json([
            'success' => true,
            'status'  => $statusMasuk,
            'nama'    => $namaLcd,
            'kelas'   => $departemen,
            'jam'     => $jamDisplay,
            'message' => ($statusMasuk === 'Hadir') ? 'Absensi Masuk Berhasil' : 'Absen Masuk (Terlambat)',
        ]);
    }

    /**
     * Endpoint Heartbeat: Melaporkan status kesehatan mesin & sinkronisasi jam
     * POST /api/v1/iot/heartbeat
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $request->validate([
            'device_id'    => 'required|string|max:50',
            'wifi_rssi'    => 'nullable|numeric',
            'fw_version'   => 'nullable|string|max:20',
            'scans_today'  => 'nullable|integer',
        ]);

        $deviceId = $request->input('device_id');
        $device = AttendanceDevice::where('device_id', $deviceId)->first();

        if ($device) {
            $device->updateHeartbeat($request->ip(), $request->input('fw_version'));
        }

        return response()->json([
            'success'            => true,
            'server_time'        => now()->toIso8601String(),
            'heartbeat_interval' => 60,
            'message'            => 'Heartbeat ACK OK',
        ]);
    }

    /**
     * Endpoint Konfigurasi Mesin: Mengirim parameter operasional ke ESP32
     * POST /api/v1/iot/config
     */
    public function config(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string|max:50',
        ]);

        $deviceId = $request->input('device_id');
        $device = AttendanceDevice::where('device_id', $deviceId)->first();

        $settingSiswa = AttendanceSetting::getForRole('siswa');
        $settingPegawai = AttendanceSetting::getForRole('pegawai');

        return response()->json([
            'success'     => true,
            'server_time' => now()->toIso8601String(),
            'device'      => [
                'device_id' => $device->device_id ?? $deviceId,
                'nama'      => $device->nama ?? 'Mesin Absensi',
                'lokasi'    => $device->lokasi ?? 'Pintu Utama',
            ],
            'schedules'   => [
                'siswa' => [
                    'jam_masuk'       => substr($settingSiswa->jam_masuk, 0, 5),
                    'batas_terlambat' => substr($settingSiswa->batas_terlambat, 0, 5),
                    'jam_pulang'      => substr($settingSiswa->jam_pulang, 0, 5),
                ],
                'pegawai' => [
                    'jam_masuk'       => substr($settingPegawai->jam_masuk, 0, 5),
                    'batas_terlambat' => substr($settingPegawai->batas_terlambat, 0, 5),
                    'jam_pulang'      => substr($settingPegawai->jam_pulang, 0, 5),
                ],
            ],
        ]);
    }

    /**
     * Endpoint Cek Update Firmware OTA
     * GET /api/v1/iot/firmware-check
     */
    public function firmwareCheck(Request $request): JsonResponse
    {
        $currentVersion = $request->query('current_version', 'v2.0.0');
        $latestVersion = config('iot.firmware.latest_version', 'v2.0.0');

        $hasUpdate = version_compare(ltrim($latestVersion, 'v'), ltrim($currentVersion, 'v'), '>');

        if ($hasUpdate && config('iot.firmware.download_url')) {
            return response()->json([
                'update_available' => true,
                'version'          => $latestVersion,
                'download_url'     => config('iot.firmware.download_url'),
                'checksum_sha256'  => config('iot.firmware.checksum_sha256', ''),
            ]);
        }

        return response()->json([
            'update_available' => false,
            'version'          => $currentVersion,
            'message'          => 'Firmware is up to date',
        ]);
    }

    /**
     * Format nama agar pas di LCD 16x2 (Maksimal 16 karakter tanpa terpotong jelek)
     */
    private function formatLcdName(string $name): string
    {
        $name = trim($name);
        if (mb_strlen($name) <= 16) {
            return $name;
        }

        // Singkat nama belakang jika terlalu panjang, misal: "Ahmad Fauzi Ramadhan" -> "Ahmad Fauzi R."
        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            $shortened = $parts[0];
            for ($i = 1; $i < count($parts); $i++) {
                if (mb_strlen($shortened . ' ' . $parts[$i]) <= 16) {
                    $shortened .= ' ' . $parts[$i];
                } else {
                    $shortened .= ' ' . mb_substr($parts[$i], 0, 1) . '.';
                    break;
                }
            }
            if (mb_strlen($shortened) <= 16) {
                return $shortened;
            }
        }

        return mb_substr($name, 0, 16);
    }

    /**
     * Simpan log scan mentah untuk histori & audit
     */
    private function saveLog(array $data): void
    {
        try {
            AttendanceScanLog::create($data);
        } catch (\Exception $e) {
            Log::warning('Gagal menyimpan attendance_scan_logs: ' . $e->getMessage());
        }
    }

    /**
     * Endpoint Data Real-time untuk Live Monitor Dashboard & TV Kiosk
     * GET /api/v1/iot/monitor/data
     */
    public function liveMonitorData(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $attendances = Attendance::with('user')
            ->where('date', $today)
            ->orderBy('updated_at', 'desc')
            ->limit(24)
            ->get();

        $records = $attendances->map(function ($absen) {
            $user = $absen->user;
            $nama = $user ? $user->name : 'Pengguna';
            $kelas = $user ? ($user->class_or_department ?? $user->role ?? 'Siswa/Staff') : '-';
            $foto = $user && isset($user->avatar_url) ? $user->avatar_url : null;

            return [
                'id'             => $absen->id,
                'user_id'        => $absen->user_id,
                'nama'           => $nama,
                'inisial'        => mb_strtoupper(mb_substr($nama, 0, 1)),
                'kelas'          => $kelas,
                'foto'           => $foto,
                'jam_masuk'      => $absen->check_in_time ? Carbon::parse($absen->check_in_time)->format('H:i') : '--:--',
                'jam_pulang'     => $absen->check_out_time ? Carbon::parse($absen->check_out_time)->format('H:i') : '--:--',
                'status'         => $absen->status ?? 'Hadir',
                'device_id'      => $absen->device_id ?? 'GERBANG-01',
                'time_formatted' => $absen->updated_at ? $absen->updated_at->diffForHumans() : 'Baru saja',
                'updated_at'     => $absen->updated_at ? $absen->updated_at->toIso8601String() : now()->toIso8601String(),
            ];
        });

        // Statistik ringkasan hari ini
        $totalHadir = Attendance::where('date', $today)->where('status', 'Hadir')->count();
        $totalTelat = Attendance::where('date', $today)->where('status', 'Telat')->count();
        $totalPulang = Attendance::where('date', $today)->whereNotNull('check_out_time')->count();

        return response()->json([
            'records' => $records,
            'stats'   => [
                'total_today' => $attendances->count(),
                'hadir'       => $totalHadir,
                'telat'       => $totalTelat,
                'pulang'      => $totalPulang,
                'server_time' => now()->format('H:i:s'),
                'server_date' => now()->translatedFormat('l, d F Y'),
            ],
        ]);
    }

    /**
     * Tampilan Halaman Live Monitor Real-time
     * GET /absensi/monitor
     */
    public function liveMonitor()
    {
        return view('iot.live_monitor');
    }
}

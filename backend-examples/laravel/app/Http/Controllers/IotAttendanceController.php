<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\AttendanceScanLog;
use App\Models\AttendanceSetting;
use App\Models\IotSetting;
use App\Models\UnregisteredCard;
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

        // 1. Update status online dan heartbeat perangkat
        if ($deviceId) {
            $device = AttendanceDevice::where('device_id', $deviceId)->first();
            if ($device) {
                $device->increment('scans_today');
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

            // Rekam ke antrean pendaftaran kartu cepat (Card Enrollment Buffer)
            try {
                UnregisteredCard::recordCard($code, $jenis, $deviceId);
            } catch (\Exception $e) {
                Log::debug('Gagal mencatat unregistered card: ' . $e->getMessage());
            }

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
     * Endpoint Heartbeat: Melaporkan status kesehatan mesin, RSSI sinyal, & sinkronisasi jam
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
            $device->updateHeartbeat(
                $request->ip(),
                $request->input('fw_version'),
                $request->input('wifi_rssi'),
                $request->input('scans_today')
            );
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

        $iotSetting = IotSetting::getActive();
        $settingSiswa = AttendanceSetting::getForRole('siswa');
        $settingPegawai = AttendanceSetting::getForRole('pegawai');

        $namaLembaga = $iotSetting->nama_lembaga ?? config('iot.institution_name', 'SMK NEGERI 1 BOGOR');
        $standbyText = $device->standby_text_override ?? ($iotSetting->standby_text ?? config('iot.standby_text', 'SILAKAN ABSEN'));
        $tipeSensor = $device->tipe_sensor ?? 'COMBO';

        return response()->json([
            'success'      => true,
            'nama_lembaga' => $this->formatLcdName($namaLembaga),
            'tipe_sensor'  => $tipeSensor,
            'standby_text' => $standbyText,
            'server_time'  => now()->toIso8601String(),
            'device'       => [
                'device_id' => $device->device_id ?? $deviceId,
                'nama'      => $device->nama ?? 'Mesin Absensi',
                'lokasi'    => $device->lokasi ?? 'Pintu Utama',
            ],
            'schedules'    => [
                'siswa' => [
                    'jam_masuk'       => substr($settingSiswa->jam_masuk ?? '07:00:00', 0, 5),
                    'batas_terlambat' => substr($settingSiswa->batas_terlambat ?? '07:30:00', 0, 5),
                    'jam_pulang'      => substr($settingSiswa->jam_pulang ?? '14:00:00', 0, 5),
                ],
                'pegawai' => [
                    'jam_masuk'       => substr($settingPegawai->jam_masuk ?? '07:30:00', 0, 5),
                    'batas_terlambat' => substr($settingPegawai->batas_terlambat ?? '08:00:00', 0, 5),
                    'jam_pulang'      => substr($settingPegawai->jam_pulang ?? '16:00:00', 0, 5),
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
     * Endpoint Data Real-time untuk Live Monitor Dashboard & TV Kiosk
     * GET /api/v1/iot/monitor/data
     */
    public function liveMonitorData(Request $request): JsonResponse
    {
        $today = Carbon::today()->toDateString();

        $attendances = Attendance::with('user')
            ->where('tanggal', $today)
            ->orderBy('updated_at', 'desc')
            ->limit(24)
            ->get();

        $records = $attendances->map(function ($absen) {
            $user = $absen->user;
            $nama = $user ? $user->name : 'Pengguna';
            $kelas = $user ? ($user->department ?? $user->role ?? 'Siswa/Staff') : '-';
            $foto = $user && isset($user->avatar_url) ? $user->avatar_url : null;

            return [
                'id'             => $absen->id,
                'user_id'        => $absen->user_id,
                'nama'           => $nama,
                'inisial'        => mb_strtoupper(mb_substr($nama, 0, 1)),
                'kelas'          => $kelas,
                'foto'           => $foto,
                'jam_masuk'      => $absen->jam_masuk ? substr($absen->jam_masuk, 0, 5) : '--:--',
                'jam_pulang'     => $absen->jam_pulang ? substr($absen->jam_pulang, 0, 5) : '--:--',
                'status'         => $absen->status ?? 'Hadir',
                'device_id'      => $absen->device_id_masuk ?? 'GERBANG-01',
                'time_formatted' => $absen->updated_at ? $absen->updated_at->diffForHumans() : 'Baru saja',
                'updated_at'     => $absen->updated_at ? $absen->updated_at->toIso8601String() : now()->toIso8601String(),
            ];
        });

        // Statistik ringkasan hari ini
        $totalToday = Attendance::where('tanggal', $today)->count();
        $totalHadir = Attendance::where('tanggal', $today)->where('status', 'Hadir')->count();
        $totalTelat = Attendance::where('tanggal', $today)->where('status', 'Telat')->count();
        $totalPulang = Attendance::where('tanggal', $today)->whereNotNull('jam_pulang')->count();

        return response()->json([
            'records' => $records,
            'stats'   => [
                'total_today' => $totalToday,
                'hadir'       => $totalHadir,
                'telat'       => $totalTelat,
                'pulang'      => $totalPulang,
                'server_time' => now()->format('H:i:s'),
                'server_date' => now()->translatedFormat('l, d F Y'),
            ],
        ]);
    }

    /**
     * Tampilan Halaman Live Monitor Real-time (TV Lobi)
     * GET /absensi/monitor
     */
    public function liveMonitor()
    {
        return view('iot.live_monitor');
    }

    /**
     * Tampilan Halaman Device Monitoring & Pengaturan IoT (Web Admin)
     * GET /absensi/devices
     */
    public function deviceMonitoring()
    {
        return view('iot.device_monitoring');
    }

    /**
     * Data Telemetri Perangkat IoT, Status Sinyal, dan Pendaftaran Kartu
     * GET /api/v1/iot/devices/data
     */
    public function deviceMonitoringData(Request $request): JsonResponse
    {
        $devices = AttendanceDevice::orderBy('nama', 'asc')->get()->map(function ($d) {
            $isOnline = $d->isOnline();
            $lastSeen = $d->last_heartbeat ? $d->last_heartbeat->diffForHumans() : 'Belum pernah';

            return [
                'id'                    => $d->id,
                'device_id'             => $d->device_id,
                'nama'                  => $d->nama,
                'lokasi'                => $d->lokasi,
                'token'                 => $d->token,
                'tipe_sensor'           => $d->tipe_sensor ?? 'COMBO',
                'is_online'             => $isOnline,
                'status'                => $isOnline ? 'online' : 'offline',
                'signal_quality'        => $d->signal_quality,
                'wifi_rssi'             => $d->wifi_rssi,
                'scans_today'           => $d->scans_today ?? 0,
                'standby_text_override' => $d->standby_text_override,
                'last_heartbeat'        => $d->last_heartbeat ? $d->last_heartbeat->toIso8601String() : null,
                'last_seen'             => $lastSeen,
                'ip_address'            => $d->ip_address ?? '-',
                'firmware_version'      => $d->firmware_version ?? 'v2.0.0',
            ];
        });

        $onlineCount = $devices->where('is_online', true)->count();
        $offlineCount = $devices->where('is_online', false)->count();
        $totalScans = $devices->sum('scans_today');

        $iotSetting = IotSetting::getActive();
        $settingSiswa = AttendanceSetting::getForRole('siswa');
        $settingPegawai = AttendanceSetting::getForRole('pegawai');

        // Ambil 15 kartu terbaru yang belum terdaftar (untuk Enrollment)
        $unregisteredCards = UnregisteredCard::orderBy('last_scanned_at', 'desc')->limit(15)->get();

        // Ambil daftar pengguna tanpa kartu / seluruh pengguna untuk dropdown assignment
        $users = User::select('id', 'name', 'role', 'department', 'identifier_number', 'rfid_uid', 'barcode_uid')
            ->orderBy('name', 'asc')
            ->limit(500)
            ->get();

        return response()->json([
            'summary' => [
                'total_devices' => $devices->count(),
                'online'        => $onlineCount,
                'offline'       => $offlineCount,
                'total_scans'   => $totalScans,
            ],
            'devices'            => $devices,
            'unregistered_cards' => $unregisteredCards,
            'users'              => $users,
            'settings'           => [
                'nama_lembaga'             => $iotSetting->nama_lembaga,
                'standby_text'             => $iotSetting->standby_text,
                'timezone'                 => $iotSetting->timezone,
                'duplicate_window_seconds' => $iotSetting->duplicate_window_seconds,
                'schedules'                => [
                    'siswa' => [
                        'jam_masuk'       => substr($settingSiswa->jam_masuk ?? '07:00:00', 0, 5),
                        'batas_terlambat' => substr($settingSiswa->batas_terlambat ?? '07:30:00', 0, 5),
                        'jam_pulang'      => substr($settingSiswa->jam_pulang ?? '14:00:00', 0, 5),
                    ],
                    'pegawai' => [
                        'jam_masuk'       => substr($settingPegawai->jam_masuk ?? '07:30:00', 0, 5),
                        'batas_terlambat' => substr($settingPegawai->batas_terlambat ?? '08:00:00', 0, 5),
                        'jam_pulang'      => substr($settingPegawai->jam_pulang ?? '16:00:00', 0, 5),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Simpan Pengaturan Profil Lembaga & Jam Absensi
     * POST /api/v1/iot/settings/save
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $request->validate([
            'nama_lembaga'             => 'required|string|max:100',
            'standby_text'             => 'required|string|max:50',
            'duplicate_window_seconds' => 'nullable|integer|min:5|max:300',
            'siswa_masuk'              => 'nullable|string',
            'siswa_terlambat'          => 'nullable|string',
            'siswa_pulang'             => 'nullable|string',
            'pegawai_masuk'            => 'nullable|string',
            'pegawai_terlambat'        => 'nullable|string',
            'pegawai_pulang'           => 'nullable|string',
        ]);

        $setting = IotSetting::getActive();
        $setting->update([
            'nama_lembaga'             => $request->input('nama_lembaga'),
            'standby_text'             => $request->input('standby_text'),
            'duplicate_window_seconds' => $request->input('duplicate_window_seconds', 30),
        ]);

        // Simpan jadwal siswa
        AttendanceSetting::updateOrCreate(
            ['tipe_user' => 'siswa'],
            [
                'jam_masuk'       => $request->input('siswa_masuk', '07:00:00'),
                'batas_terlambat' => $request->input('siswa_terlambat', '07:30:00'),
                'jam_pulang'      => $request->input('siswa_pulang', '14:00:00'),
            ]
        );

        // Simpan jadwal pegawai
        AttendanceSetting::updateOrCreate(
            ['tipe_user' => 'pegawai'],
            [
                'jam_masuk'       => $request->input('pegawai_masuk', '07:30:00'),
                'batas_terlambat' => $request->input('pegawai_terlambat', '08:00:00'),
                'jam_pulang'      => $request->input('pegawai_pulang', '16:00:00'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan lembaga dan jam berhasil disimpan',
        ]);
    }

    /**
     * Daftarkan Kartu Baru ke Pengguna (Card Enrollment)
     * POST /api/v1/iot/cards/enroll
     */
    public function enrollCard(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'code'    => 'required|string|max:128',
            'jenis'   => 'nullable|string|in:RFID,BARCODE',
        ]);

        $userId = $request->input('user_id');
        $code = trim($request->input('code'));
        $jenis = strtoupper($request->input('jenis', 'RFID'));

        $user = User::findOrFail($userId);

        if ($jenis === 'RFID') {
            $user->rfid_uid = $code;
        } else {
            $user->barcode_uid = $code;
        }
        $user->save();

        // Hapus dari antrean unregistered cards
        UnregisteredCard::where('code', $code)->delete();

        return response()->json([
            'success' => true,
            'message' => "Kartu {$code} berhasil dipasangkan ke {$user->name}",
        ]);
    }

    /**
     * Hapus Kartu dari Antrean Unregistered
     * DELETE /api/v1/iot/cards/unregistered/{id}
     */
    public function deleteUnregisteredCard($id): JsonResponse
    {
        UnregisteredCard::destroy($id);
        return response()->json(['success' => true, 'message' => 'Kartu dihapus dari antrean']);
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
}

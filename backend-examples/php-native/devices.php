<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring & Pengaturan Mesin IoT Absensi (PHP Native)</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .lcd-screen {
            font-family: 'Courier New', Courier, monospace;
            background-color: #0b2e13;
            color: #72ff72;
            text-shadow: 0 0 4px #72ff72;
            letter-spacing: 2px;
        }
    </style>
</head>
<body class="h-full antialiased font-sans flex flex-col bg-slate-950 text-slate-100 selection:bg-emerald-500 selection:text-white"
      x-data="phpNativeDeviceManager()"
      x-init="init()"
      x-cloak>

    <!-- Header Bar -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col md:flex-row items-center justify-between gap-4">
            
            <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-start">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-600 to-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-white flex items-center gap-2">
                            Pusat Kontrol Perangkat IoT (PHP Native)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/10 text-cyan-400 border border-cyan-500/30">
                                v2.0.0
                            </span>
                        </h1>
                        <p class="text-xs text-slate-400" x-text="'Lembaga: ' + (settings.nama_lembaga || 'Memuat...')"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/80 border border-slate-700/60 text-xs">
                    <span :class="isSyncing ? 'animate-spin' : ''" class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                    <span x-text="isSyncing ? 'Sinkron...' : 'Live 4s'"></span>
                </div>
            </div>

            <!-- Tabs & Links -->
            <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto justify-start md:justify-end">
                <button @click="activeTab = 'devices'"
                        :class="activeTab === 'devices' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800'"
                        class="px-3.5 py-1.5 rounded-xl border border-slate-700/60 text-xs font-semibold flex items-center gap-1.5 transition">
                    <span>Mesin Absensi (<span x-text="devices.length">0</span>)</span>
                </button>

                <button @click="activeTab = 'enrollment'"
                        :class="activeTab === 'enrollment' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800'"
                        class="px-3.5 py-1.5 rounded-xl border border-slate-700/60 text-xs font-semibold flex items-center gap-1.5 transition relative">
                    <span>Pendaftaran Kartu</span>
                    <template x-if="unregisteredCards.length > 0">
                        <span class="w-4 h-4 bg-rose-500 text-white text-[10px] rounded-full flex items-center justify-center font-bold" x-text="unregisteredCards.length"></span>
                    </template>
                </button>

                <button @click="activeTab = 'settings'"
                        :class="activeTab === 'settings' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-slate-800/80 text-slate-300 hover:bg-slate-800'"
                        class="px-3.5 py-1.5 rounded-xl border border-slate-700/60 text-xs font-semibold flex items-center gap-1.5 transition">
                    <span>Setting Lembaga</span>
                </button>

                <a href="monitor.php" target="_blank"
                   class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold flex items-center gap-1 transition">
                    <span>Layar TV</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Top Metric Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
            <div class="p-4 rounded-2xl bg-slate-900/70 border border-slate-800 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Mesin IoT</div>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-2xl font-extrabold text-white" x-text="summary.total_devices || 0">0</span>
                    <span class="text-xs text-slate-400 font-mono">Unit</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-800/40 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">Mesin Online</div>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-2xl font-extrabold text-emerald-400" x-text="summary.online || 0">0</span>
                    <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                        Aktif
                    </span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-rose-950/30 border border-rose-800/40 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-rose-400">Mesin Offline</div>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-2xl font-extrabold text-rose-400" x-text="summary.offline || 0">0</span>
                    <span class="text-[11px] text-rose-400/80 font-medium">> 3 mnt</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-cyan-950/30 border border-cyan-800/40 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-cyan-400">Total Scan Hari Ini</div>
                <div class="mt-1 flex items-baseline justify-between">
                    <span class="text-2xl font-extrabold text-cyan-400" x-text="summary.total_scans || 0">0</span>
                    <span class="text-xs text-cyan-400/80 font-mono">Tap Kartu</span>
                </div>
            </div>
        </div>

        <!-- TAB 1: LIST MESIN ABSENSI -->
        <section x-show="activeTab === 'devices'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-white">Status Real-time Seluruh Mesin Smart Box</h2>
                    <p class="text-xs text-slate-400">Status otomatis offline jika mesin tidak mengirim heartbeat lebih dari 3 menit.</p>
                </div>
                <button @click="loadData()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 border border-slate-700 flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <span>Refresh</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="device in devices" :key="device.id">
                    <div class="rounded-2xl border p-5 transition relative overflow-hidden backdrop-blur-sm"
                         :class="device.is_online ? 'bg-slate-900/80 border-slate-700/80 shadow-lg shadow-emerald-950/20' : 'bg-slate-900/40 border-rose-900/40'">
                        
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-white" x-text="device.nama"></h3>
                                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700" x-text="device.device_id"></span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /></svg>
                                    <span x-text="device.lokasi || 'Lokasi belum disetel'"></span>
                                </p>
                            </div>

                            <div class="shrink-0">
                                <template x-if="device.is_online">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        ONLINE
                                    </span>
                                </template>
                                <template x-if="!device.is_online">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                        OFFLINE
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Telemetri Data -->
                        <div class="mt-4 pt-3 border-t border-slate-800/80 space-y-2.5 text-xs">
                            <div>
                                <div class="flex items-center justify-between text-slate-400 mb-1">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" /></svg>
                                        Sinyal WiFi
                                    </span>
                                    <span class="font-mono font-bold"
                                          :class="device.wifi_rssi >= -70 ? 'text-emerald-400' : (device.wifi_rssi >= -85 ? 'text-amber-400' : 'text-rose-400')">
                                        <span x-text="device.wifi_rssi ? device.wifi_rssi + ' dBm' : 'N/A'"></span>
                                        <span class="text-[10px] font-normal" x-text="'(' + (device.signal_quality?.label || '-') + ')'"></span>
                                    </span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500"
                                         :style="'width: ' + (device.signal_quality?.percentage || 0) + '%'"
                                         :class="device.wifi_rssi >= -70 ? 'bg-emerald-500' : (device.wifi_rssi >= -85 ? 'bg-amber-500' : 'bg-rose-500')"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-slate-300 pt-1">
                                <div class="p-2 rounded-lg bg-slate-800/50">
                                    <span class="text-[10px] text-slate-500 uppercase block">Total Scan Hari Ini</span>
                                    <span class="font-mono font-bold text-white text-sm" x-text="device.scans_today || 0">0</span>
                                </div>
                                <div class="p-2 rounded-lg bg-slate-800/50">
                                    <span class="text-[10px] text-slate-500 uppercase block">Versi Firmware</span>
                                    <span class="font-mono font-bold text-cyan-400 text-sm" x-text="device.firmware_version">v2.0.0</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1">
                                <span>IP: <span class="font-mono text-slate-200" x-text="device.ip_address || '-'"></span></span>
                                <span>Heartbeat: <span class="text-slate-200" x-text="device.last_seen"></span></span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800/60 flex items-center justify-between text-xs">
                            <span class="text-[11px] text-slate-500 font-mono truncate max-w-[200px]" x-text="'Token: ' + device.token"></span>
                            <button @click="copyText(device.token)" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-[10px] font-semibold text-slate-300">
                                Salin Token
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- TAB 2: PENDAFTARAN KARTU (CARD ENROLLMENT) -->
        <section x-show="activeTab === 'enrollment'" class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-white">Antrean Pendaftaran Kartu Baru (Quick Enrollment)</h2>
                <p class="text-xs text-slate-400">
                    Tempelkan kartu RFID baru atau scan QR baru pada mesin fisik. UID akan masuk ke antrean di bawah untuk langsung dipasangkan ke Siswa / Karyawan.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 backdrop-blur-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-800/60 border-b border-slate-700/60 text-slate-300 uppercase font-semibold text-[10px] tracking-wider">
                            <tr>
                                <th class="p-3.5">Kode Kartu / UID</th>
                                <th class="p-3.5">Jenis Sensor</th>
                                <th class="p-3.5">Mesin Tap</th>
                                <th class="p-3.5">Waktu Tap Terakhir</th>
                                <th class="p-3.5">Total Tap</th>
                                <th class="p-3.5">Pasangkan ke Siswa / Karyawan</th>
                                <th class="p-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <template x-if="unregisteredCards.length === 0">
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-500">
                                        Belum ada kartu baru yang di-tap. Tempelkan kartu RFID atau scan QR baru ke mesin fisik untuk memulai pendaftaran.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="card in unregisteredCards" :key="card.id">
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="p-3.5 font-mono font-bold text-emerald-400 text-sm" x-text="card.code"></td>
                                    <td class="p-3.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                                              :class="card.jenis === 'RFID' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40' : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40'"
                                              x-text="card.jenis"></span>
                                    </td>
                                    <td class="p-3.5 font-mono text-slate-400" x-text="card.device_id || '-'"></td>
                                    <td class="p-3.5 text-slate-400" x-text="formatDate(card.last_scanned_at)"></td>
                                    <td class="p-3.5 font-bold text-white" x-text="card.tap_count + 'x'"></td>
                                    <td class="p-3.5">
                                        <div class="flex items-center gap-2">
                                            <select x-model="enrollmentMap[card.id]" class="bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white max-w-[220px]">
                                                <option value="">-- Pilih Siswa / Karyawan --</option>
                                                <template x-for="u in users" :key="u.id">
                                                    <option :value="u.id" x-text="u.name + ' (' + (u.department || u.role) + ')'"></option>
                                                </template>
                                            </select>
                                            <button @click="enrollCard(card.id, card.code, card.jenis)"
                                                    :disabled="!enrollmentMap[card.id]"
                                                    class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-semibold text-xs transition">
                                                Simpan
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-center">
                                        <button @click="deleteUnregisteredCard(card.id)" class="text-rose-400 hover:text-rose-300 text-xs">
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- TAB 3: PENGATURAN LEMBAGA -->
        <section x-show="activeTab === 'settings'" class="space-y-6">
            <div>
                <h2 class="text-lg font-bold text-white">Pengaturan Identitas Lembaga & Layar LCD 16x2</h2>
                <p class="text-xs text-slate-400">Parameter ini dikirimkan otomatis ke mesin ESP32 saat proses booting melalui endpoint <code class="text-cyan-400">api/config.php</code>.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 rounded-2xl border border-slate-800 bg-slate-900/60 p-5 space-y-4 backdrop-blur-sm">
                    <form @submit.prevent="saveSettings()">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Nama Lembaga / Sekolah (Maksimal 16 Karakter LCD)</label>
                                <input type="text" maxlength="30" x-model="formSettings.nama_lembaga" required
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
                                <p class="text-[11px] text-slate-500 mt-1">Ditampilkan pada <strong>Baris 1 LCD</strong> mesin saat posisi standby.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Teks Standby LCD</label>
                                <input type="text" maxlength="16" x-model="formSettings.standby_text" required
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
                                <p class="text-[11px] text-slate-500 mt-1">Ditampilkan pada <strong>Baris 2 LCD</strong> (contoh: <code>SILAKAN ABSEN</code> atau <code>TEMPEL KARTU</code>).</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-800">
                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Proteksi Anti-Duplicate (Detik)</label>
                                    <input type="number" min="5" max="300" x-model="formSettings.duplicate_window_seconds" required
                                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Zona Waktu Server</label>
                                    <input type="text" x-model="formSettings.timezone" disabled
                                           class="w-full bg-slate-800/60 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-400">
                                </div>
                            </div>

                            <!-- Jadwal Siswa & Pegawai -->
                            <div class="pt-4 border-t border-slate-800 space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Jadwal Presensi Siswa</h3>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="text-[10px] text-slate-400 block mb-1">Jam Masuk</label>
                                        <input type="time" step="1" x-model="formSettings.siswa_masuk" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-slate-400 block mb-1">Batas Terlambat</label>
                                        <input type="time" step="1" x-model="formSettings.siswa_terlambat" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-slate-400 block mb-1">Jam Pulang</label>
                                        <input type="time" step="1" x-model="formSettings.siswa_pulang" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-800 space-y-3">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-cyan-400">Jadwal Presensi Pegawai / Guru</h3>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="text-[10px] text-slate-400 block mb-1">Jam Masuk</label>
                                        <input type="time" step="1" x-model="formSettings.pegawai_masuk" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-slate-400 block mb-1">Batas Terlambat</label>
                                        <input type="time" step="1" x-model="formSettings.pegawai_terlambat" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-slate-400 block mb-1">Jam Pulang</label>
                                        <input type="time" step="1" x-model="formSettings.pegawai_pulang" class="w-full bg-slate-800 border border-slate-700 rounded-lg p-2 text-xs text-white">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4">
                                <button type="submit" :disabled="isSaving"
                                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                                    <span x-show="!isSaving">Simpan Perubahan Pengaturan</span>
                                    <span x-show="isSaving">Menyimpan...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 backdrop-blur-sm space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Simulasi Tampilan Layar LCD Mesin (16x2)</h3>
                    <p class="text-[11px] text-slate-500">Tampilan pada LCD fisik saat mesin standby:</p>
                    
                    <div class="p-4 rounded-xl border border-emerald-900 bg-slate-950 shadow-inner space-y-2">
                        <div class="lcd-screen p-3 rounded-lg text-sm font-bold uppercase">
                            <div class="truncate" x-text="pad16(formSettings.nama_lembaga)">SMK NEGERI 1</div>
                            <div class="truncate mt-1" x-text="pad16(formSettings.standby_text)">SILAKAN ABSEN</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function phpNativeDeviceManager() {
            return {
                activeTab: 'devices',
                isSyncing: false,
                isSaving: false,
                devices: [],
                unregisteredCards: [],
                users: [],
                summary: {},
                settings: {},
                enrollmentMap: {},
                formSettings: {
                    action: 'save_settings',
                    nama_lembaga: '',
                    standby_text: '',
                    duplicate_window_seconds: 30,
                    timezone: 'Asia/Jakarta',
                    siswa_masuk: '07:00:00',
                    siswa_terlambat: '07:30:00',
                    siswa_pulang: '14:00:00',
                    pegawai_masuk: '07:30:00',
                    pegawai_terlambat: '08:00:00',
                    pegawai_pulang: '16:00:00',
                },

                init() {
                    this.loadData();
                    setInterval(() => this.loadData(true), 4000);
                },

                async loadData(background = false) {
                    if (!background) this.isSyncing = true;
                    try {
                        const res = await fetch('api/device_data.php');
                        const data = await res.json();
                        this.devices = data.devices || [];
                        this.summary = data.summary || {};
                        this.settings = data.settings || {};
                        this.unregisteredCards = data.unregistered_cards || [];
                        this.users = data.users || [];

                        if (!this.formSettings.nama_lembaga && data.settings) {
                            this.formSettings.nama_lembaga = data.settings.nama_lembaga || '';
                            this.formSettings.standby_text = data.settings.standby_text || '';
                            this.formSettings.duplicate_window_seconds = data.settings.duplicate_window_seconds || 30;
                            if (data.settings.schedules?.siswa) {
                                this.formSettings.siswa_masuk = data.settings.schedules.siswa.jam_masuk + ':00';
                                this.formSettings.siswa_terlambat = data.settings.schedules.siswa.batas_terlambat + ':00';
                                this.formSettings.siswa_pulang = data.settings.schedules.siswa.jam_pulang + ':00';
                            }
                            if (data.settings.schedules?.pegawai) {
                                this.formSettings.pegawai_masuk = data.settings.schedules.pegawai.jam_masuk + ':00';
                                this.formSettings.pegawai_terlambat = data.settings.schedules.pegawai.batas_terlambat + ':00';
                                this.formSettings.pegawai_pulang = data.settings.schedules.pegawai.jam_pulang + ':00';
                            }
                        }
                    } catch (e) {
                        console.error('Fetch error:', e);
                    } finally {
                        this.isSyncing = false;
                    }
                },

                async saveSettings() {
                    this.isSaving = true;
                    try {
                        const res = await fetch('api/device_data.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.formSettings)
                        });
                        const result = await res.json();
                        if (result.success) {
                            alert('Pengaturan lembaga berhasil disimpan!');
                            this.loadData();
                        } else {
                            alert('Gagal: ' + (result.message || 'Error'));
                        }
                    } catch (e) {
                        alert('Gagal menghubungi server: ' + e.message);
                    } finally {
                        this.isSaving = false;
                    }
                },

                async enrollCard(cardId, code, jenis) {
                    const userId = this.enrollmentMap[cardId];
                    if (!userId) return;

                    try {
                        const res = await fetch('api/device_data.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'enroll_card', user_id: userId, code: code, jenis: jenis })
                        });
                        const result = await res.json();
                        if (result.success) {
                            delete this.enrollmentMap[cardId];
                            this.loadData();
                        } else {
                            alert('Gagal: ' + result.message);
                        }
                    } catch (e) {
                        alert('Error: ' + e.message);
                    }
                },

                async deleteUnregisteredCard(cardId) {
                    if (!confirm('Hapus kartu ini dari antrean?')) return;
                    try {
                        await fetch('api/device_data.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'delete_card', id: cardId })
                        });
                        this.loadData();
                    } catch (e) {}
                },

                pad16(str) {
                    str = String(str || '').trim();
                    return str.length > 16 ? str.substring(0, 16) : str.padEnd(16, ' ');
                },

                formatDate(dateStr) {
                    if (!dateStr) return '-';
                    const d = new Date(dateStr);
                    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                },

                copyText(text) {
                    navigator.clipboard.writeText(text);
                    alert('Token disalin ke clipboard!');
                }
            };
        }
    </script>
</body>
</html>

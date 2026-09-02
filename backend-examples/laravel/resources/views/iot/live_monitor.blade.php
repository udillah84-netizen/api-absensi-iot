<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Monitor Absensi IoT | Real-time Stream</title>

    <!-- Tailwind CSS CDN (Siap pakai tanpa build step) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#10b981',
                            600: '#059669',
                        }
                    },
                    animation: {
                        'pulse-glow': 'pulseGlow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'spin-slow': 'spin 3s linear infinite',
                    },
                    keyframes: {
                        pulseGlow: {
                            '0%, 100%': { opacity: '1', transform: 'scale(1)' },
                            '50%': { opacity: '.6', transform: 'scale(1.04)' },
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        @keyframes flashBorder {
            0% { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.6); transform: scale(1.02); }
            50% { box-shadow: 0 0 25px rgba(16, 185, 129, 0.8); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); transform: scale(1); }
        }
        .card-new-arrival {
            animation: flashBorder 2.5s ease-out;
        }
    </style>
</head>
<body class="h-full antialiased font-sans flex flex-col bg-slate-950 text-slate-100 selection:bg-emerald-500 selection:text-white"
      x-data="liveMonitorApp()"
      x-init="init()">

    <!-- Top Header Bar -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-4">
            
            <!-- Logo & Status -->
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-white flex items-center gap-2">
                            Live Stream Presensi IoT
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                LIVE
                            </span>
                        </h1>
                        <p class="text-xs text-slate-400" x-text="stats.server_date || 'Hari ini'"></p>
                    </div>
                </div>

                <!-- Polling Status -->
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-800/80 border border-slate-700/60 text-xs">
                    <template x-if="!error">
                        <div class="flex items-center gap-1.5 text-emerald-400 font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span>Terhubung</span>
                        </div>
                    </template>
                    <template x-if="error">
                        <div class="flex items-center gap-1.5 text-rose-400 font-medium">
                            <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                            <span>Mencoba Hubung Ulang...</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Stats & Action Toolbar -->
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <!-- Clock -->
                <div class="px-3.5 py-1.5 rounded-xl bg-slate-800 border border-slate-700 font-mono text-sm font-bold text-emerald-400 shadow-inner">
                    <span x-text="liveClock">00:00:00</span>
                </div>

                <!-- Audio Chime Toggle -->
                <button @click="toggleAudio()"
                        :class="audioEnabled ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 text-slate-400 border-slate-700'"
                        class="p-2 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition-colors"
                        title="Bunyi Notifikasi Saat Tap Kartu">
                    <svg x-show="audioEnabled" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /></svg>
                    <svg x-show="!audioEnabled" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" /></svg>
                    <span class="hidden md:inline" x-text="audioEnabled ? 'Suara ON' : 'Suara OFF'"></span>
                </button>

                <!-- Fullscreen Toggle -->
                <button @click="toggleFullscreen()"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 transition-colors"
                        title="Mode Fullscreen / Layar TV Lobi">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                </button>
            </div>
        </div>

        <!-- Quick Stats Strip -->
        <div class="border-t border-slate-800/80 bg-slate-900/50 py-2.5">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-4 gap-2 text-center">
                <div class="p-2 rounded-xl bg-slate-800/60 border border-slate-700/50">
                    <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Hari Ini</div>
                    <div class="text-lg sm:text-xl font-extrabold text-white" x-text="stats.total_today || 0">0</div>
                </div>
                <div class="p-2 rounded-xl bg-emerald-950/40 border border-emerald-800/50">
                    <div class="text-[10px] uppercase font-bold text-emerald-400 tracking-wider">Hadir Tepat</div>
                    <div class="text-lg sm:text-xl font-extrabold text-emerald-400" x-text="stats.hadir || 0">0</div>
                </div>
                <div class="p-2 rounded-xl bg-amber-950/40 border border-amber-800/50">
                    <div class="text-[10px] uppercase font-bold text-amber-400 tracking-wider">Terlambat</div>
                    <div class="text-lg sm:text-xl font-extrabold text-amber-400" x-text="stats.telat || 0">0</div>
                </div>
                <div class="p-2 rounded-xl bg-cyan-950/40 border border-cyan-800/50">
                    <div class="text-[10px] uppercase font-bold text-cyan-400 tracking-wider">Sudah Pulang</div>
                    <div class="text-lg sm:text-xl font-extrabold text-cyan-400" x-text="stats.pulang || 0">0</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Stream Area -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <!-- Empty State -->
        <div x-show="!loading && records.length === 0" x-cloak class="rounded-3xl border border-dashed border-slate-800 bg-slate-900/40 p-16 text-center">
            <div class="w-20 h-20 mx-auto rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-emerald-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-white">Menunggu Tap Kartu / Scan Barcode...</h3>
            <p class="text-sm text-slate-400 mt-1 max-w-md mx-auto">Mesin IoT standby. Setiap kali kartu RFID atau QR discan, data akan langsung muncul di layar ini secara otomatis.</p>
        </div>

        <!-- Attendance Stream Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
            <template x-for="item in records" :key="item.id">
                <div class="relative bg-slate-900/90 rounded-2xl border border-slate-800 p-4 shadow-md transition-all duration-300 hover:border-slate-700"
                     :class="{'card-new-arrival ring-2 ring-emerald-500 bg-slate-800/90': isNew(item.id)}">
                    
                    <!-- Header Kartu: Status Pill & Jam -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wide"
                              :class="{
                                  'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': item.status === 'Hadir',
                                  'bg-amber-500/20 text-amber-400 border border-amber-500/30': item.status === 'Telat',
                                  'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30': item.status === 'Pulang',
                                  'bg-slate-700 text-slate-300': !['Hadir','Telat','Pulang'].includes(item.status)
                              }"
                              x-text="item.status"></span>

                        <span class="text-[10px] font-medium text-slate-400" x-text="item.time_formatted"></span>
                    </div>

                    <!-- Profile Section -->
                    <div class="flex items-center gap-3.5">
                        <!-- Avatar / Inisial -->
                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-gradient-to-tr from-slate-800 to-slate-700 border border-slate-700 flex items-center justify-center shrink-0">
                            <template x-if="item.foto">
                                <img :src="item.foto" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!item.foto">
                                <span class="text-lg font-black text-emerald-400" x-text="item.inisial || item.nama.charAt(0)"></span>
                            </template>
                        </div>

                        <!-- Name & Dept/Class -->
                        <div class="min-w-0 flex-1">
                            <h4 class="text-sm font-bold text-white truncate" x-text="item.nama"></h4>
                            <p class="text-xs text-slate-400 truncate mt-0.5" x-text="item.kelas"></p>
                        </div>
                    </div>

                    <!-- Check-in / Check-out Times -->
                    <div class="mt-3.5 pt-3 border-t border-slate-800/80 grid grid-cols-2 gap-2 text-xs font-medium">
                        <div class="flex items-center gap-1.5 text-slate-300">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                            <span>Masuk: <b class="text-white font-mono" x-text="item.jam_masuk"></b></span>
                        </div>
                        <div class="flex items-center gap-1.5 text-slate-300 justify-end">
                            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            <span>Pulang: <b class="text-white font-mono" x-text="item.jam_pulang"></b></span>
                        </div>
                    </div>

                    <!-- Device Identifier Pin -->
                    <div class="mt-2 text-[9px] text-slate-500 font-mono flex items-center justify-between">
                        <span x-text="'ID: ' + item.device_id"></span>
                        <span class="text-emerald-500/80">✓ Verified IoT</span>
                    </div>
                </div>
            </template>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-950 py-3 text-center text-xs text-slate-500">
        Mesin Absensi Smart Box ESP32 • Sistem Presensi Real-time
    </footer>

    <!-- Alpine App Script -->
    <script>
        function liveMonitorApp() {
            return {
                records: [],
                stats: {},
                loading: true,
                error: false,
                newIds: [],
                audioEnabled: false,
                audioCtx: null,
                liveClock: '--:--:--',

                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);

                    this.fetchData();
                    // Polling real-time setiap 3 detik
                    setInterval(() => this.fetchData(true), 3000);
                },

                updateClock() {
                    const now = new Date();
                    this.liveClock = now.toTimeString().split(' ')[0];
                },

                toggleAudio() {
                    this.audioEnabled = !this.audioEnabled;
                    if (this.audioEnabled && !this.audioCtx) {
                        this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        this.playChime(660, 0.1);
                    }
                },

                playChime(freq = 880, duration = 0.15) {
                    if (!this.audioEnabled) return;
                    try {
                        if (!this.audioCtx) {
                            this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        }
                        const osc = this.audioCtx.createOscillator();
                        const gain = this.audioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, this.audioCtx.currentTime);
                        gain.gain.setValueAtTime(0.15, this.audioCtx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + duration);
                        osc.connect(gain);
                        gain.connect(this.audioCtx.destination);
                        osc.start();
                        osc.stop(this.audioCtx.currentTime + duration);
                    } catch (e) {
                        console.warn('Audio playback not supported:', e);
                    }
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(() => {});
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        }
                    }
                },

                async fetchData(isPolling = false) {
                    if (!isPolling) this.loading = true;

                    try {
                        const res = await fetch('/api/v1/iot/monitor/data', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!res.ok) throw new Error('HTTP ' + res.status);

                        const data = await res.json();
                        const incomingRecords = data.records || [];
                        this.stats = data.stats || {};

                        // Deteksi kartu baru untuk animasi neon glow & audio chime
                        if (isPolling && this.records.length > 0) {
                            const currentIds = this.records.map(r => r.id);
                            const incomingNew = incomingRecords
                                .map(r => r.id)
                                .filter(id => !currentIds.includes(id));

                            if (incomingNew.length > 0) {
                                this.newIds = [...this.newIds, ...incomingNew];
                                this.playChime(987, 0.2); // Notifikasi chime saat ada tap baru

                                setTimeout(() => {
                                    this.newIds = this.newIds.filter(id => !incomingNew.includes(id));
                                }, 4000);
                            }
                        }

                        this.records = incomingRecords;
                        this.error = false;
                    } catch (err) {
                        console.error('Polling error:', err);
                        this.error = true;
                    } finally {
                        this.loading = false;
                    }
                },

                isNew(id) {
                    return this.newIds.includes(id);
                }
            }
        }
    </script>
</body>
</html>

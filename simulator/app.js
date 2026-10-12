/**
 * ====================================================================
 * JAVASCRIPT: IoT ESP32 Smart Box Hardware Simulator Logic
 * ====================================================================
 */

// State
let isSoundEnabled = true;
let isOfflineMode = false;
let offlineQueue = [];
let logHistory = [];
let audioCtx = null;
let idleTimer = null;

// DOM Elements
const lcdLine1 = document.getElementById('lcd-line-1');
const lcdLine2 = document.getElementById('lcd-line-2');
const ledPwr = document.getElementById('led-pwr');
const ledWifi = document.getElementById('led-wifi');
const ledScan = document.getElementById('led-scan');
const ledSd = document.getElementById('led-sd');
const labelWifi = document.getElementById('label-wifi');
const jsonResponseEl = document.getElementById('json-response');
const jsonRequestEl = document.getElementById('json-request');
const logsContainer = document.getElementById('logs-container');
const httpStatusBadge = document.getElementById('http-status-badge');
const btnSoundToggle = document.getElementById('btn-sound-toggle');
const btnToggleOffline = document.getElementById('btn-toggle-offline');
const btnSyncOffline = document.getElementById('btn-sync-offline');
const offlineCountEl = document.getElementById('offline-count');
const logCountEl = document.getElementById('log-count');

// Initialize Web Audio API on first user interaction
function getAudioContext() {
  if (!audioCtx) {
    const AudioContextClass = window.AudioContext || window.webkitAudioContext;
    if (AudioContextClass) {
      audioCtx = new AudioContextClass();
    }
  }
  if (audioCtx && audioCtx.state === 'suspended') {
    audioCtx.resume();
  }
  return audioCtx;
}

/**
 * Synthesize buzzer tone frequencies using Web Audio API
 */
function playTone(freq, durationMs, delayMs = 0) {
  if (!isSoundEnabled) return;
  const ctx = getAudioContext();
  if (!ctx) return;

  setTimeout(() => {
    try {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();

      osc.type = 'square'; // Typical active buzzer square wave
      osc.frequency.setValueAtTime(freq, ctx.currentTime);

      gain.gain.setValueAtTime(0.12, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + durationMs / 1000);

      osc.connect(gain);
      gain.connect(ctx.destination);

      osc.start();
      osc.stop(ctx.currentTime + durationMs / 1000);
    } catch (e) {
      console.warn('Audio error:', e);
    }
  }, delayMs);
}

/**
 * Buzzer sound presets matching firmware spec
 */
const Buzzer = {
  success() {
    // 2x short high beeps (2400 Hz)
    playTone(2400, 80, 0);
    playTone(2400, 80, 120);
  },
  notice() {
    // 1x medium long beep (1800 Hz)
    playTone(1800, 350, 0);
  },
  error() {
    // 3x fast warning beeps (1000 Hz)
    playTone(1000, 100, 0);
    playTone(1000, 100, 150);
    playTone(1000, 100, 300);
  },
  offlineSave() {
    // 1x short beep (2000 Hz)
    playTone(2000, 90, 0);
  }
};

/**
 * Pad / truncate string to exactly 16 characters for LCD 16x2
 */
function pad16(str) {
  str = String(str || '').trim();
  if (str.length > 16) {
    return str.substring(0, 16);
  }
  return str.padEnd(16, ' ');
}

/**
 * Set LCD text
 */
function setLcd(line1, line2, autoResetSec = 4) {
  lcdLine1.textContent = pad16(line1);
  lcdLine2.textContent = pad16(line2);

  if (idleTimer) clearTimeout(idleTimer);
  if (autoResetSec > 0) {
    idleTimer = setTimeout(() => {
      startIdleClock();
    }, autoResetSec * 1000);
  }
}

let currentHardwareVariant = 'COMBO'; // 'COMBO' | 'RFID' | 'BARCODE'

/**
 * Handle hardware variant change
 */
function changeHardwareVariant(variant) {
  currentHardwareVariant = variant;
  const manualJenis = document.getElementById('manual-jenis');
  if (manualJenis) {
    if (variant === 'RFID') manualJenis.value = 'RFID';
    if (variant === 'BARCODE') manualJenis.value = 'BARCODE';
  }
  addLog(`🔧 Varian hardware diubah: ${variant}`, 'info');
  startIdleClock();
}

/**
 * Clock idle screen based on hardware variant
 */
function startIdleClock() {
  const now = new Date();
  const timeStr = now.toTimeString().substring(0, 5);
  const dateStr = now.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit' });

  let statusStr = 'READY TO SCAN';
  if (isOfflineMode) {
    statusStr = '[OFFLINE BUFF]';
  } else if (currentHardwareVariant === 'RFID') {
    statusStr = 'TEMPELKAN KARTU';
  } else if (currentHardwareVariant === 'BARCODE') {
    statusStr = 'ARAHKAN KODE QR';
  } else {
    statusStr = 'SILAKAN ABSEN';
  }

  setLcd(`ESP32 ${timeStr} ${dateStr}`, statusStr, 0);
}

// Start clock update interval
setInterval(() => {
  if (
    lcdLine2.textContent.includes('READY') ||
    lcdLine2.textContent.includes('SILAKAN') ||
    lcdLine2.textContent.includes('TEMPELKAN') ||
    lcdLine2.textContent.includes('ARAHKAN') ||
    lcdLine2.textContent.includes('[OFFLINE BUFF]')
  ) {
    startIdleClock();
  }
}, 1000);

/**
 * Flash Scan ACT LED
 */
function flashActLed() {
  ledScan.classList.add('active');
  setTimeout(() => {
    ledScan.classList.remove('active');
  }, 350);
}

/**
 * Add log entry
 */
function addLog(msg, type = 'info') {
  const time = new Date().toLocaleTimeString('id-ID');
  const entry = `[${time}] ${msg}`;
  logHistory.unshift(entry);
  if (logHistory.length > 100) logHistory.pop();

  logCountEl.textContent = logHistory.length;
  logsContainer.innerHTML = logHistory.map(l => `<div class="log-entry">${l}</div>`).join('');
}

/**
 * Simulate Scan Execution
 */
async function simulateScan(code, jenis = 'RFID', label = '') {
  getAudioContext();
  flashActLed();

  const targetUrl = document.getElementById('target-url').value.trim();
  const secretToken = document.getElementById('secret-token').value.trim();
  const deviceId = document.getElementById('device-id').value.trim();
  const scannedAt = new Date().toISOString();

  // Visual sensor feedback
  const zone = jenis === 'RFID' ? document.getElementById('zone-rfid') : document.getElementById('zone-barcode');
  zone.classList.add('pulse-scan');
  setTimeout(() => zone.classList.remove('pulse-scan'), 400);

  const payload = {
    code: code,
    device_id: deviceId,
    scanned_at: scannedAt,
    jenis: jenis
  };

  jsonRequestEl.textContent = JSON.stringify(payload, null, 2);
  addLog(`Scan ${jenis}: ${code} (${label || 'Custom'})`);

  // Skenario Offline Mode
  if (isOfflineMode) {
    offlineQueue.push(payload);
    ledSd.classList.add('active');
    offlineCountEl.textContent = offlineQueue.length;
    btnSyncOffline.style.display = 'inline-flex';

    setLcd('OFFLINE SAVED', `BUF: ${offlineQueue.length} REC`, 3);
    Buzzer.offlineSave();
    addLog(`📦 Data disimpan ke SD Card Buffer (${offlineQueue.length} pending)`, 'warning');
    return;
  }

  // Skenario Online Mode: Kirim ke Backend API
  setLcd('PROCESSING...', code.substring(0, 16), 0);
  httpStatusBadge.textContent = 'Sending...';
  httpStatusBadge.className = 'badge';

  const startTime = performance.now();

  try {
    const response = await fetch(targetUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Device-Token': secretToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const elapsed = Math.round(performance.now() - startTime);
    const data = await response.json().catch(() => ({}));

    jsonResponseEl.textContent = JSON.stringify(data, null, 2);

    httpStatusBadge.textContent = `HTTP ${response.status} (${elapsed}ms)`;

    if (response.ok && data.success) {
      httpStatusBadge.className = 'badge badge-success';
      const line1 = data.nama || 'BERHASIL';
      const line2 = `${(data.status || 'HADIR').toUpperCase()} - ${data.jam || 'OK'}`;
      setLcd(line1, line2, 4);
      Buzzer.success();
      addLog(`✅ Sukses: ${data.nama} [${data.status}] (${elapsed}ms)`);
    } else if (response.status === 401) {
      httpStatusBadge.className = 'badge badge-danger';
      setLcd('HTTP ERR: 401', 'UNAUTHORIZED', 4);
      Buzzer.error();
      addLog(`❌ 401 Unauthorized: Cek X-Device-Token`, 'error');
    } else if (response.status === 404 || data.status === 'Gagal') {
      httpStatusBadge.className = 'badge badge-danger';
      setLcd('KARTU DITOLAK', 'Tidak Terdaftar', 4);
      Buzzer.error();
      addLog(`❌ Kartu Tidak Terdaftar: ${code}`, 'error');
    } else {
      httpStatusBadge.className = 'badge badge-danger';
      const line1 = data.nama || 'DITOLAK';
      const line2 = (data.message || data.status || 'GAGAL').substring(0, 16);
      setLcd(line1, line2, 4);
      Buzzer.notice();
      addLog(`⚠️ Ditolak: ${data.message || 'Status: ' + data.status}`);
    }
  } catch (err) {
    httpStatusBadge.textContent = 'Network Error';
    httpStatusBadge.className = 'badge badge-danger';
    jsonResponseEl.textContent = `// Gagal menghubungi server:\n${err.message}\n\nPastikan backend Anda sudah berjalan dan mendukung CORS.`;
    setLcd('CONN ERROR / OFFL', 'CEK BACKEND URL', 4);
    Buzzer.error();
    addLog(`❌ Gagal terhubung ke server: ${err.message}`, 'error');
  }
}

/**
 * Handle Manual Scan Form Submit
 */
function handleManualScan(e) {
  e.preventDefault();
  const code = document.getElementById('manual-code').value.trim();
  const jenis = document.getElementById('manual-jenis').value;
  if (!code) {
    alert('Masukkan UID RFID atau kode barcode terlebih dahulu!');
    return;
  }
  simulateScan(code, jenis, 'Manual Input');
}

/**
 * Send Heartbeat
 */
async function sendHeartbeat() {
  getAudioContext();
  const targetUrl = document.getElementById('target-url').value.trim().replace(/\/scan\/?$/, '/heartbeat');
  const secretToken = document.getElementById('secret-token').value.trim();
  const deviceId = document.getElementById('device-id').value.trim();

  addLog('💓 Mengirim sinyal heartbeat...');

  try {
    const response = await fetch(targetUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Device-Token': secretToken
      },
      body: JSON.stringify({
        device_id: deviceId,
        wifi_rssi: -62,
        fw_version: 'v2.0.0',
        scans_today: 42
      })
    });

    const data = await response.json();
    jsonResponseEl.textContent = JSON.stringify(data, null, 2);
    addLog(`💓 Heartbeat response: ${JSON.stringify(data)}`);
    setLcd('HEARTBEAT ACK', 'SERVER SYNC OK', 2);
    playTone(2000, 60);
  } catch (e) {
    addLog(`❌ Heartbeat failed: ${e.message}`, 'error');
  }
}

/**
 * Fetch Device Config (Booting Simulation)
 */
async function fetchConfig() {
  getAudioContext();
  const targetUrl = document.getElementById('target-url').value.trim().replace(/\/scan\/?$/, '/config');
  const secretToken = document.getElementById('secret-token').value.trim();
  const deviceId = document.getElementById('device-id').value.trim();

  addLog('⚙️ Mengambil konfigurasi perangkat (Booting)...');

  try {
    const response = await fetch(targetUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Device-Token': secretToken
      },
      body: JSON.stringify({
        device_id: deviceId
      })
    });

    const data = await response.json();
    jsonResponseEl.textContent = JSON.stringify(data, null, 2);
    addLog(`⚙️ Config response: ${JSON.stringify(data)}`);
    if (data.success) {
      const line1 = data.nama_lembaga || 'CONFIG OK';
      const line2 = data.standby_text || 'READY TO SCAN';
      setLcd(line1, line2, 5);
      playTone(2200, 100);
      addLog(`✅ Config diterima: ${line1} | Standby: ${line2}`);
    } else {
      setLcd('CONFIG REJECTED', (data.message || 'FAILED').substring(0, 16), 3);
      Buzzer.error();
    }
  } catch (e) {
    addLog(`❌ Fetch config failed: ${e.message}`, 'error');
  }
}

/**
 * Toggle Offline Mode
 */
function toggleOfflineMode() {
  isOfflineMode = !isOfflineMode;
  if (isOfflineMode) {
    ledWifi.classList.remove('active');
    labelWifi.textContent = 'NO-WIFI';
    btnToggleOffline.textContent = '📶 Mode: OFFLINE (SIMULATED)';
    btnToggleOffline.classList.add('btn-warning');
    addLog('⚠️ Mode Offline diaktifkan. Data scan akan masuk ke antrean SD card.');
  } else {
    ledWifi.classList.add('active');
    labelWifi.textContent = 'WIFI';
    btnToggleOffline.textContent = '📶 Mode: ONLINE';
    btnToggleOffline.classList.remove('btn-warning');
    addLog('🌐 Mode Online kembali aktif.');
  }
  startIdleClock();
}

/**
 * Synchronize Offline Buffer
 */
async function syncOfflineQueue() {
  if (offlineQueue.length === 0) return;
  addLog(`🚀 Memulai background auto-sync ${offlineQueue.length} data antrean SD Card...`);

  const queue = [...offlineQueue];
  offlineQueue = [];
  offlineCountEl.textContent = '0';
  btnSyncOffline.style.display = 'none';
  ledSd.classList.remove('active');

  for (let i = 0; i < queue.length; i++) {
    const item = queue[i];
    setLcd('SYNCING SD...', `${i + 1}/${queue.length} RECS`, 0);
    await simulateScan(item.code, item.jenis, `Sync Buf #${i + 1}`);
    await new Promise(r => setTimeout(r, 600)); // Delay antar antrean
  }

  setLcd('SYNC COMPLETED', `${queue.length} DATA SYNCED`, 3);
  addLog(`✅ Seluruh antrean SD Card berhasil disinkronkan ke server.`);
}

/**
 * Preset Target URLs
 */
function presetUrl(type) {
  const urlInput = document.getElementById('target-url');
  if (type === 'laravel') {
    urlInput.value = 'http://localhost:8000/api/v1/iot/scan';
  } else if (type === 'express') {
    urlInput.value = 'http://localhost:3000/api/v1/iot/scan';
  } else if (type === 'php') {
    urlInput.value = 'http://localhost/api-absensi-iot/backend-examples/php-native/api/scan.php';
  }
  addLog(`Target URL disetel ke: ${urlInput.value}`);
}

/**
 * Switch Inspector Tab
 */
function switchTab(tabId) {
  document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.inspector-tabs .tab').forEach(el => el.classList.remove('active'));

  document.getElementById(tabId).style.display = 'block';
  event.target.classList.add('active');
}

/**
 * Reset LCD
 */
function resetLcd() {
  startIdleClock();
  addLog('Layar LCD di-reset ke tampilan idle.');
}

// Sound toggle
btnSoundToggle.addEventListener('click', () => {
  isSoundEnabled = !isSoundEnabled;
  btnSoundToggle.textContent = isSoundEnabled ? '🔊 Sound: ON' : '🔇 Sound: MUTED';
  btnSoundToggle.classList.toggle('btn-secondary', !isSoundEnabled);
});

// Initial boot
startIdleClock();

# 🤖 AGENTS.md - IoT Attendance System Master Guide

Panduan kerja dan instruksi arsitektur bagi AI Agent (Antigravity, Claude Code, Cursor, Copilot, Aider, Gemini) yang bertugas membantu klien mengintegrasikan **Mesin Absensi IoT ESP32 (RFID + Barcode/QR 2D)** ke dalam aplikasi web mereka.

---

## 🎯 Tujuan Utama Proyek

Repositori ini menyediakan starter kit, pustaka backend, skema database, simulator hardware, dan dokumentasi integrasi untuk mesin absensi fisik **Smart Box ESP32 (Firmware v2.0.0+)**.

Klien biasanya sudah memiliki website sendiri (Laravel, PHP Native, Node.js, Python, Golang, dsb.) dan ingin menghubungkan mesin fisik yang mereka beli tanpa perlu merombak arsitektur sistem mereka.

---

## 🚨 Aturan Kritis & Batasan yang Tidak Boleh Dilanggar

Sebagai AI Agent, Anda **WAJIB MEMATUHI** aturan berikut:

1. **JANGAN MERUSAK KONTRAK API (`skillscan.md` & `docs/API_REFERENCE.md`)**:
   - Endpoint utama adalah `POST /api/v1/iot/scan` (menerima field: `code`, `device_id`, `scanned_at`, `jenis`).
   - Endpoint pendukung: `POST /api/v1/iot/heartbeat`, `POST /api/v1/iot/config`, `GET /api/v1/iot/firmware-check`.
2. **FORMAT BALASAN HARUS RAMAH LCD 16x2**:
   - Field `nama` pada response JSON **maksimal 16 karakter** per baris.
   - Field `status` dan `jam` ditampilkan di baris ke-2 (contoh: `HADIR - 07:15` atau `TELAT - 07:45`).
   - Jangan mengirim response HTML / Debug Page! Respon harus selalu JSON yang valid dengan Content-Type `application/json`.
3. **PENGECUALIAN CSRF (CROSS-SITE REQUEST FORGERY)**:
   - Mesin ESP32 adalah *standalone hardware* dan **tidak memiliki sesi web/CSRF token**.
   - Route `/api/v1/iot/*` atau `/iot/*` **wajib dikecualikan dari proteksi CSRF**.
4. **HEADER AUTENTIKASI FLEKSIBEL**:
   - Mesin mengirim token melalui header `X-Device-Token`.
   - Backend harus mendukung validasi `X-Device-Token`, `X-IoT-Token`, atau `Authorization: Bearer <token>`.
5. **PENCEGAHAN DOUBLE TAP (ANTI-SPAM)**:
   - Pengguna sering menempelkan kartu 2x tidak sengaja. Backend wajib menerapkan *anti-duplicate window* (jeda 30-60 detik) agar tidak mencatat 2x absen masuk.
6. **PENANGANAN TIMESTAMP OFFLINE SYNC (`scanned_at`)**:
   - Jika koneksi internet mesin sempat putus, data scan disimpan di SD Card dan dikirim belakangan.
   - Gunakan nilai timestamp `scanned_at` dari payload (bukan `now()`) jika parameter `scanned_at` disediakan.

---

## 🗺️ Peta Direktori & Navigasi Framework

| Tech Stack Klien | Lokasi Starter Kit | File Panduan Khusus |
|---|---|---|
| **Laravel (10-13)** | `backend-examples/laravel/` | `backend-examples/laravel/AGENTS.md` & `SKILL.md` |
| **PHP Native / cPanel** | `backend-examples/php-native/` | `backend-examples/php-native/AGENTS.md` & `SKILL.md` |
| **Node.js (Express)** | `backend-examples/nodejs-express/` | `backend-examples/nodejs-express/AGENTS.md` & `SKILL.md` |
| **Python (FastAPI)** | `backend-examples/python-fastapi/` | `backend-examples/python-fastapi/AGENTS.md` & `SKILL.md` |
| **Golang (Gin)** | `backend-examples/golang-gin/` | `backend-examples/golang-gin/AGENTS.md` & `SKILL.md` |

---

## 🧪 Alur Verifikasi & Pengujian Wajib

Setelah Anda menambahkan atau memodifikasi kode integrasi untuk klien:
1. Buka dan uji endpoint menggunakan **Web Hardware Simulator** di [`simulator/index.html`](file:///d:/ubay/00.projek-website/api-absensi-iot/simulator/index.html).
2. Uji 5 skenario utama:
   - ✅ Scan Hadir Tepat Waktu (HTTP 200)
   - 🟡 Scan Hadir Terlambat (HTTP 200, Status: Telat)
   - 🔵 Scan Pulang (HTTP 200, Status: Pulang)
   - ⚪ Double Tap Barusan (HTTP 200, Status: Hadir, pesan: Sudah Absen Barusan)
   - 🔴 Kartu Belum Terdaftar (HTTP 404)
   - 🔴 Token Unauthorized (HTTP 401)

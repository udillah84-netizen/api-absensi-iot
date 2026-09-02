# 🤖 AGENTS.md - Node.js Express Integration Guide

Panduan kerja khusus AI Agent saat mengintegrasikan modul **Mesin Absensi IoT ESP32** ke dalam proyek **Node.js (Express / Fastify / NestJS)**.

---

## 📌 Konteks & Arsitektur

- **Framework:** Express.js (v4.19+)
- **Format Payload:** JSON Body parsing (`express.json()`)
- **Middleware:** `authMiddleware.js` (validasi `X-Device-Token`)
- **Controller:** `iotController.js` (logika bisnis presensi, double-tap protection, LCD format)

---

## 🚨 Aturan Kritis Node.js yang Harus Dipatuhi

1. **Body Parser JSON Harus Aktif Sebelum Rute**:
   ```javascript
   app.use(express.json());
   ```
   Tanpa middleware ini, `req.body` akan bernilai `undefined`.
2. **CORS Middleware untuk Simulator Browser**:
   ```javascript
   const cors = require('cors');
   app.use(cors());
   ```
   Dibutuhkan agar simulator di `simulator/index.html` dapat memanggil API lokal tanpa terblokir oleh kebijakan Same-Origin Policy browser.
3. **Penanganan Waktu (Timezone)**:
   - Server Node.js default berjalan di UTC.
   - Gunakan format waktu lokal Indonesia (WIB) atau parsing string waktu secara konsisten.
4. **Respon Status Code**:
   - Kartu tidak terdaftar &rarr; HTTP 404.
   - Token salah / hilang &rarr; HTTP 401.
   - Sukses presensi (termasuk Terlambat & Pulang) &rarr; HTTP 200.

---

## 🛠️ Perintah CLI Utama

```bash
# Install dependencies
npm install

# Menjalankan development server (auto-reload)
npm run dev

# Menjalankan production server
npm start
```

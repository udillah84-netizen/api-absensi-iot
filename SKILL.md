---
name: iot-attendance-integration
description: Panduan dan workflow lengkap integrasi hardware Mesin Absensi IoT ESP32 (RFID & Barcode/QR 2D) ke berbagai platform web backend (Laravel, PHP Native, Node.js, Python, Golang).
---

# IoT Attendance Integration Skill

Skill ini memandu AI Agent dalam mengeksekusi integrasi mesin absensi fisik IoT ESP32 ke sistem web klien secara cepat, aman, dan tanpa merusak arsitektur yang sudah berjalan.

---

## 🎯 Prosedur Integrasi Bertahap (Standard Operating Procedure)

Ketika klien meminta: *"Tolong hubungkan mesin absensi IoT / RFID / Barcode ke website saya"*, jalankan langkah-langkah terstruktur berikut:

### Langkah 1: Identifikasi Tech Stack & Database Klien
1. Periksa berkas proyek klien:
   - Jika terdapat `composer.json` dan `artisan` &rarr; **Laravel Stack**.
   - Jika terdapat `index.php` / `.htaccess` / cPanel &rarr; **PHP Native / CI Stack**.
   - Jika terdapat `package.json` &rarr; **Node.js Stack**.
   - Jika terdapat `requirements.txt` / `pyproject.toml` &rarr; **Python Stack**.
   - Jika terdapat `go.mod` &rarr; **Golang Stack**.
2. Periksa jenis RDBMS yang digunakan (MySQL, PostgreSQL, atau SQLite).

---

### Langkah 2: Persiapan Database (Database Migration)
1. **Tambahkan Kolom Kartu:**
   Pastikan tabel master pengguna klien (misal `users`, `students`, atau `employees`) memiliki kolom identifikasi:
   - `rfid_uid VARCHAR(64) UNIQUE NULL`
   - `barcode_uid VARCHAR(64) UNIQUE NULL`
2. **Buat Tabel Penunjang:**
   - Tabel `attendance_devices` (pencatatan token & status online mesin).
   - Tabel `attendances` (transaksi masuk/pulang).
   - Tabel `attendance_scan_logs` (audit log scan mentah).
   - Tabel `attendance_settings` (jam masuk, batas telat, jam pulang).

---

### Langkah 3: Implementasi Autentikasi Token (Middleware)
Pasang middleware untuk memvalidasi header request:
```http
X-Device-Token: <SECRET_TOKEN>
```
Validasi kecocokan dengan token global di `.env` (`IOT_SECRET_TOKEN`) atau token unik per perangkat di tabel `attendance_devices`.

---

### Langkah 4: Implementasi Controller & Endpoint API
Buat endpoint `POST /api/v1/iot/scan` dengan alur logika:
1. Validasi input: `code` (string wajib), `device_id` (opsional), `scanned_at` (opsional), `jenis` (opsional).
2. Cari pengguna di database (`rfid_uid`, `barcode_uid`, `identifier_number`, atau `id`).
3. Jika tidak ditemukan &rarr; Kembalikan HTTP 404: `{"success": false, "status": "Gagal", "message": "Kartu Tidak Terdaftar"}`.
4. Cek jeda double-tap (< 30 detik) &rarr; Kembalikan HTTP 200 data yang sudah tercatat.
5. Cek jam masuk vs jam pulang:
   - Jika sudah ada jam masuk & waktu sudah mencapai jam pulang &rarr; Update `jam_pulang` (Status: `Pulang`).
   - Jika belum pernah absen hari ini &rarr; Catat `jam_masuk`, tentukan status `Hadir` atau `Telat`.
6. Kembalikan response JSON ramah LCD 16x2.

---

### Langkah 5: Pengecualian Proteksi CSRF
Pastikan route `/api/v1/iot/*` atau `/iot/*` tidak dicegat oleh CSRF middleware / token form web.

---

### Langkah 6: Verifikasi & Uji Coba
Buka [`simulator/index.html`](file:///d:/ubay/00.projek-website/api-absensi-iot/simulator/index.html) dan lakukan simulasi scan:
- Tap preset kartu terdaftar &rarr; Cek status LCD `HADIR` / `TELAT` + nada buzzer.
- Tap preset kartu tidak terdaftar &rarr; Cek status LCD `KARTU DITOLAK` (HTTP 404) + 3x beep error.
- Uji mode Offline Buffer & Sync.

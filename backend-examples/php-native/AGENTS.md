# 🤖 AGENTS.md - PHP Native / WordPress / cPanel Integration Guide

Panduan kerja khusus AI Agent saat mengintegrasikan modul **Mesin Absensi IoT ESP32** ke dalam proyek **PHP Native, CodeIgniter 3/4, WordPress, atau Shared Hosting (cPanel)**.

---

## 📌 Karakteristik Stack Ini

- Tidak bergantung pada Composer atau framework tertentu.
- Menggunakan ekstensi bawaan PHP: `PDO (MySQL)`, `json`, dan `mbstring`.
- Cocok untuk hosting cPanel sederhana di mana akses SSH / terminal terbatas.

---

## 🚨 Aturan Kritis PHP Native yang Harus Dipatuhi

1. **Gunakan Header JSON & Content-Type yang Tepat**:
   ```php
   header('Content-Type: application/json; charset=utf-8');
   ```
   Jangan biarkan ada output karakter/spasi kosong sebelum tag `<?php` yang dapat merusak struktur JSON.
2. **Penanganan `getallheaders()` pada Apache / Nginx / FastCGI**:
   - Fungsi `getallheaders()` kadang tidak tersedia di lingkungan FastCGI tertentu.
   - Gunakan fallback `$_SERVER['HTTP_X_DEVICE_TOKEN']` atau `$_SERVER['HTTP_AUTHORIZATION']`.
3. **Prepared Statements Wajib Digunakan**:
   - Hindari penggabungan string query SQL mentah untuk mencegah SQL Injection dari parameter `code` atau `device_id`.
4. **Zona Waktu Server**:
   - Pastikan menyetel `date_default_timezone_set('Asia/Jakarta');` di awal skrip agar pencatatan jam presensi akurat.

---

## 🔍 Checklist Verifikasi AI Agent

- [ ] File `config.php` telah disesuaikan dengan nama database dan password MySQL server hosting.
- [ ] File skema `sql/schema_mysql.sql` sudah diimpor ke phpMyAdmin.
- [ ] Akses URL `https://domain-klien.com/api/scan.php` menghasilkan respon JSON (bukan blank page atau error PHP).
- [ ] Pengujian berhasil via Simulator atau cURL.

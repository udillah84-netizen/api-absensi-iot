# 🐘 Panduan Integrasi PHP Native / CodeIgniter / WordPress / cPanel

Folder ini ditujukan bagi Anda yang menggunakan:
- PHP Native / Vanilla PHP
- Framework PHP Klasik (CodeIgniter 3/4, Yii, CakePHP)
- WordPress / Custom CMS
- Shared Hosting / cPanel

---

## ⚡ Cara Pemasangan di Shared Hosting / cPanel

1. **Upload Berkas**:
   - Unggah folder `api/`, `config.php`, dan `db.php` ke folder root website Anda (misal di `public_html/api/` atau `public_html/iot/`).
2. **Atur Database di `config.php`**:
   Buka `config.php` dan masukkan kredensial database MySQL Anda:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'nama_database_anda');
   define('DB_USER', 'user_database');
   define('DB_PASS', 'password_database');
   define('IOT_SECRET_TOKEN', 'tok_rahasia_mesin_anda');
   ```
3. **Import Skema Database**:
   Import berkas SQL yang ada di `../../sql/schema_mysql.sql` melalui phpMyAdmin di cPanel.
4. **Alamat URL Endpoint untuk Mesin**:
   - URL Scan: `https://domain-anda.com/api/scan.php`
   - URL Heartbeat: `https://domain-anda.com/api/heartbeat.php`

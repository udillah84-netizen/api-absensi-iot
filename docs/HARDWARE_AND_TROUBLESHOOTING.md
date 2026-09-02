# 🛠️ Panduan Hardware, Indikator & Troubleshooting

Panduan lengkap mengenai spesifikasi fisik, perkabelan, arti tampilan LCD 16x2, pola nada buzzer, konfigurasi jaringan WiFi, serta prosedur penanganan masalah (*troubleshooting*) untuk unit **Mesin Absensi Smart Box ESP32 (Firmware v2.0.0+)**.

---

## ⚡ 1. Spesifikasi Teknis Hardware

| Komponen | Spesifikasi & Parameter |
|---|---|
| **Mikrokontroler Utama** | ESP32 DevKitC V4 / ESP32-WROOM-32U (Dual-Core 240 MHz, 520 KB SRAM, FreeRTOS). |
| **Modul RFID** | RC522 SPI (Frekuensi 13.56 MHz, Standar ISO/IEC 14443 Type A, Mifare Classic 1K / S50 / Ultralight / NTAG213). |
| **Modul Barcode / QR** | Scanner Kamera 2D UART 9600 Baud (Mendukung QR Code, Code 128, EAN-13, DataMatrix, PDF417, layard HP & kertas cetak). |
| **Layar Tampilan** | LCD Karakter 16x2 HD44780 dengan Modul Adapter I2C PCF8574 (Alamat: `0x27` / `0x3F`). |
| **Audio Buzzer** | Active Buzzer 5V DC (Respon frekuensi 2400 Hz & 1000 Hz). |
| **Penyimpan Waktu (RTC)** | DS1302 / DS3231 RTC dengan Baterai Koin Cadangan CR2032 (Presisi waktu tetap akurat saat mati listrik). |
| **Penyimpanan Buffer Offline** | Slot Micro SD Card SPI (Mendukung MicroSD FAT32 hingga 32 GB) + Buffer RAM internal FreeRTOS 50 antrean. |
| **Konektivitas Nirkabel** | WiFi 802.11 b/g/n (2.4 GHz) dengan dukungan WPA/WPA2-PSK Personal. |
| **Kebutuhan Catu Daya** | Adaptor 5V DC / 2A (Konektor USB Type-C / Micro-USB / DC Jack 5.5mm). |

---

## 💡 2. Indikator Lampu LED pada Unit Mesin

Mesin dilengkapi dengan 4 LED indikator status pada panel depan:

| LED Label | Warna | Kondisi | Arti / Status Operasional |
|---|---|---|---|
| **PWR** | 🔴 Merah | Menyala Tetap | Unit mendapat suplai daya 5V DC normal. |
| **WIFI** | 🟢 Hijau | Berkedip Cepat (2 Hz) | Sedang mencoba menghubungkan ke Access Point WiFi. |
| **WIFI** | 🟢 Hijau | Menyala Tetap | Terhubung ke Access Point WiFi & berhasil memperoleh IP address. |
| **ACT** | 🔵 Biru | Berkedip 1x Singkat | Kartu RFID atau kode barcode/QR terdeteksi dan sedang diproses. |
| **SD / MEM** | 🟡 Kuning | Menyala Singkat | Sedang menulis atau menyinkronkan antrean data offline ke server. |

---

## 📟 3. Tabel Tampilan Layar LCD 16x2 & Pola Nada Buzzer

| Tampilan Layar LCD (Baris 1 & 2) | Pola Nada Buzzer | Status | Penjelasan & Solusi |
|---|---|---|---|
| `[Nama Pengguna]`<br>`HADIR - 07:15` | 2x Beep Pendek (*Ting-ting*) | 🟢 **Sukses** | Presensi masuk tercatat tepat waktu di database. |
| `[Nama Pengguna]`<br>`TELAT - 07:45` | 2x Beep Pendek | 🟡 **Terlambat** | Presensi masuk tercatat dengan status Terlambat. |
| `[Nama Pengguna]`<br>`PULANG - 14:15` | 2x Beep Pendek | 🔵 **Pulang** | Presensi pulang berhasil tercatat. |
| `[Nama Pengguna]`<br>`SUDAH ABSEN HARI` | 1x Beep Panjang (*Teet*) | ⚪ **Duplikat** | Pengguna men-tap kartu yang sama dalam jendela anti-duplicate (< 30 dtk). |
| `KARTU DITOLAK`<br>`Tidak Terdaftar` | 3x Beep Cepat (*Tet-tet-tet*) | 🔴 **Error 404** | UID kartu atau barcode belum terdaftar di tabel database. |
| `KARTU DITOLAK`<br>`Akun Nonaktif` | 3x Beep Cepat | 🔴 **Error 403** | Pengguna berstatus nonaktif atau keluar. |
| `HTTP ERR: 401`<br>`UNAUTHORIZED` | 3x Beep Cepat | 🔴 **Auth Error** | Header `X-Device-Token` pada mesin tidak cocok dengan file `.env` / konfigurasi server. |
| `WIFI TERPUTUS`<br>`MENGHUBUNGKAN` | Senyap | ⚠️ **WiFi Lost** | Sinyal WiFi hilang. Mesin mencoba reconnect otomatis ke daftar WiFi cadangan. |
| `ABSEN TERSIMPAN`<br>`OFFLINE (BUFFER)` | 1x Beep Pendek | 📦 **Offline Mode**| Server tidak dapat dijangkau. Data scan disimpan di memori dan akan dikirim otomatis saat internet kembali online. |

---

## 📶 4. Pengaturan WiFi Pertama Kali (WiFi Provisioning Mode)

Jika mesin dipasang di lokasi baru atau SSID / Password WiFi sekolah/kantor Anda berubah:

1. **Nyalakan Mesin**:
   - Jika mesin tidak dapat menemukan SSID WiFi yang tersimpan dalam daftar prioritas selama 60 detik, mesin otomatis mengaktifkan mode **Hotspot Setup**.
2. **Koneksikan Perangkat**:
   - Dari HP atau Laptop Anda, buka daftar WiFi dan sambungkan ke SSID Hotspot: **`SMARTBOX-SETUP-XXXX`** (tanpa password).
3. **Buka Captive Portal**:
   - Buka browser (Chrome/Safari) dan akses alamat IP: `http://192.168.4.1`
4. **Pilih WiFi & Simpan**:
   - Pilih nama SSID WiFi lokal Anda, masukkan password WiFi, lalu klik tombol **"Simpan & Hubungkan"**.
5. **Mesin Siap**:
   - Mesin akan merestart otomatis, lampu LED hijau menyala konstan, dan layar LCD menampilkan nama sekolah Anda!

---

## 🔍 5. Panduan Pemecahan Masalah (Troubleshooting Matrix)

### Kasus 1: Layar LCD Kosong / Hanya Kotak Hitam
* **Penyebab:** Tegangan I2C kurang atau setelan potensiometer kontras belum pas.
* **Solusi:**
  1. Putar potensiometer trimpot kecil warna biru di bagian belakang modul LCD I2C menggunakan obeng kecil hingga karakter huruf terlihat jelas dan tajam.
  2. Pastikan adaptor menggunakan minimal 5V 2A murni.

### Kasus 2: Respon "HTTP ERR: 401 / UNAUTHORIZED"
* **Penyebab:** Token keamanan yang dikirim mesin di header `X-Device-Token` tidak cocok dengan token yang Anda daftarkan di backend (`IOT_SECRET_TOKEN`).
* **Solusi:**
  1. Buka file `.env` (Laravel) atau `config.php` (PHP Native).
  2. Samakan nilai token dengan yang tercatat pada stiker nomor seri di belakang mesin Smart Box Anda.

### Kasus 3: Error "419 PAGE EXPIRED" atau Respon HTML Panjang
* **Penyebab:** Framework Anda memblokir request mesin karena proteksi CSRF (Cross-Site Request Forgery).
* **Solusi:**
  - Daftarkan route `/api/v1/iot/*` ke dalam daftar *CSRF Exemption* (contoh: di `bootstrap/app.php` Laravel 11/12/13 atau `app/Http/Middleware/VerifyCsrfToken.php` Laravel 10).

### Kasus 4: Kartu RFID Tidak Terbaca (Tidak Ada Respon / Beep)
* **Penyebab:**
  1. Jenis kartu yang digunakan bukan frekuensi 13.56 MHz (misal menggunakan kartu Proximity RFID 125 KHz EM4100).
  2. Jarak tap terlalu jauh (> 4 cm).
* **Solusi:**
  - Gunakan kartu standar Mifare Classic 1K 13.56 MHz / E-KTP / Kartu Flazz / Emoney.
  - Tempelkan kartu pada jarak 0.5 - 2 cm dari logo tap RFID di bodi mesin.

### Kasus 5: Kamera Barcode / QR Tidak Membaca Kode dari Layar HP
* **Penyebab:** Tingkat kecerahan layar HP terlalu redup atau pantulan silau (*glare*) kaca scanner.
* **Solusi:**
  - Naikkan tingkat kecerahan layar HP minimal 60%.
  - Arahkan kode QR tegak lurus pada jarak 8 - 15 cm dari lensa scanner.

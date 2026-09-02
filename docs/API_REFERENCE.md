# 📡 Dokumentasi Referensi REST API Lengkap

Dokumentasi ini adalah spesifikasi resmi seluruh endpoint REST API antara **Mesin Absensi Fisik ESP32 (Smart Box)** dan **Server Website Klien**.

---

## 🔐 1. Mekanisme Autentikasi & Header

Setiap request dari mesin ESP32 menyertakan header keamanan berikut:

```http
Content-Type: application/json
X-Device-Token: tok_rahasia_mesin_anda
```

### Fleksibilitas Header pada Backend
Untuk kemudahan pengembang, backend disarankan mendukung beberapa variasi header autentikasi:
1. `X-Device-Token: <token>` (Standar bawaan mesin ESP32)
2. `X-IoT-Token: <token>` (Alternatif)
3. `Authorization: Bearer <token>` (Standar REST API)

> ⚠️ **Catatan Penting (CSRF Bypass):**
> Mesin ESP32 adalah *standalone hardware* dan **tidak memiliki sesi web browser/CSRF token**. Seluruh rute `/api/v1/iot/*` **wajib dikecualikan dari proteksi CSRF**.

---

## 📥 2. Endpoint Scan Presensi (`POST /api/v1/iot/scan`)

Endpoint utama yang dipanggil oleh mesin ESP32 seketika saat kartu RFID didekatkan ke sensor atau kode barcode/QR terdeteksi oleh modul scanner kamera.

* **URL:** `POST /api/v1/iot/scan` (atau URL kustom seperti `https://domain-anda.com/api/scan.php`)
* **Method:** `POST`
* **Content-Type:** `application/json`

### 📥 Request Payload (JSON):
```json
{
  "code": "A1B2C3D4",
  "device_id": "DEV-GERBANG-01",
  "scanned_at": "2026-08-30T07:15:22+07:00",
  "jenis": "RFID"
}
```

#### Spesifikasi Parameter Request:
| Parameter | Tipe Data | Status | Keterangan & Contoh |
|---|---|:---:|---|
| `code` | `string` | **Wajib** | Nilai UID Kartu RFID (Hex uppercase, misal: `"A1B2C3D4"`) atau string teks isi Barcode/QR Code (misal: `"NISN-2026001"`). |
| `device_id` | `string` | Opsional | Kode identifikasi fisik perangkat mesin (misal: `"DEV-GERBANG-01"`). |
| `scanned_at` | `string` | Opsional | Timestamp ISO-8601 dari jam internal (RTC DS1302) mesin. Berguna saat sinkronisasi data *offline buffer*. |
| `jenis` | `string` | Opsional | Jenis sensor input: `"RFID"` atau `"BARCODE"`. Default: `"RFID"`. |

---

### 📤 Format Response Server:

Backend wajib mengembalikan format JSON yang ramah terhadap tampilan **LCD 16x2 I2C** pada mesin fisik.

#### 🟢 Skenario 1: Absen Masuk Sukses - Tepat Waktu (HTTP 200)
```json
{
  "success": true,
  "status": "Hadir",
  "nama": "Ahmad Fauzi",
  "kelas": "XII RPL 1",
  "jam": "07:15",
  "message": "Absensi Masuk Berhasil"
}
```
* **Layar LCD Baris 1**: `Ahmad Fauzi` (Maks 16 karakter)
* **Layar LCD Baris 2**: `HADIR - 07:15`
* **Audio Buzzer**: 2x Beep Pendek (2400 Hz)

---

#### 🟡 Skenario 2: Absen Masuk Sukses - Terlambat (HTTP 200)
```json
{
  "success": true,
  "status": "Telat",
  "nama": "Budi Santoso",
  "kelas": "XII RPL 1",
  "jam": "07:45",
  "message": "Absen Masuk (Terlambat)"
}
```
* **Layar LCD Baris 1**: `Budi Santoso`
* **Layar LCD Baris 2**: `TELAT - 07:45`
* **Audio Buzzer**: 2x Beep Pendek

---

#### 🔵 Skenario 3: Absen Pulang Sukses (HTTP 200)
```json
{
  "success": true,
  "status": "Pulang",
  "nama": "Ahmad Fauzi",
  "kelas": "XII RPL 1",
  "jam": "14:15",
  "message": "Absensi Pulang Berhasil"
}
```
* **Layar LCD Baris 1**: `Ahmad Fauzi`
* **Layar LCD Baris 2**: `PULANG - 14:15`
* **Audio Buzzer**: 2x Beep Pendek

---

#### ⚪ Skenario 4: Double-Tap Barusan / Anti-Spam (HTTP 200)
*Terjadi saat pengguna tidak sengaja menempelkan kartu 2 kali berturut-turut dalam jeda < 30 detik.*
```json
{
  "success": true,
  "status": "Hadir",
  "nama": "Ahmad Fauzi",
  "kelas": "XII RPL 1",
  "jam": "07:15",
  "message": "Sudah Absen Barusan"
}
```
* **Layar LCD Baris 1**: `Ahmad Fauzi`
* **Layar LCD Baris 2**: `SUDAH ABSEN HARI`
* **Audio Buzzer**: 1x Beep Panjang (500 ms)

---

#### 🔴 Skenario 5: Kartu Belum Terdaftar (HTTP 404)
```json
{
  "success": false,
  "status": "Gagal",
  "nama": null,
  "message": "Kartu Tidak Terdaftar"
}
```
* **Layar LCD Baris 1**: `KARTU DITOLAK`
* **Layar LCD Baris 2**: `Tidak Terdaftar`
* **Audio Buzzer**: 3x Beep Cepat (1000 Hz)

---

#### ⛔ Skenario 6: Token Otorisasi Ditolak (HTTP 401)
```json
{
  "success": false,
  "status": "Gagal",
  "message": "Unauthorized: Token Perangkat Tidak Valid"
}
```
* **Layar LCD Baris 1**: `AUTH ERROR`
* **Layar LCD Baris 2**: `TOKEN DITOLAK`
* **Audio Buzzer**: 3x Beep Cepat

---

## 💓 3. Endpoint Heartbeat & Telemetri (`POST /api/v1/iot/heartbeat`)

Dipanggil secara berkala oleh mesin ESP32 (setiap 60 detik) untuk mengirimkan status kesehatan hardware, kekuatan sinyal WiFi, dan sinkronisasi jam server.

* **URL:** `POST /api/v1/iot/heartbeat`
* **Method:** `POST`

### 📥 Request Payload:
```json
{
  "device_id": "DEV-GERBANG-01",
  "wifi_rssi": -62,
  "fw_version": "v2.0.0",
  "scans_today": 142
}
```

| Parameter | Tipe Data | Keterangan |
|---|---|---|
| `device_id` | `string` | Nomor seri perangkat. |
| `wifi_rssi` | `integer` | Kekuatan sinyal WiFi dalam dBm (contoh: `-50` sangat bagus, `-85` lemah). |
| `fw_version` | `string` | Versi firmware aktif pada ESP32. |
| `scans_today`| `integer` | Total jumlah scan kartu yang berhasil dilakukan unit hari ini. |

### 📤 Response Payload (HTTP 200):
```json
{
  "success": true,
  "server_time": "2026-08-30T07:15:22+07:00",
  "heartbeat_interval": 60,
  "message": "Heartbeat OK"
}
```

---

## ⚙️ 4. Endpoint Konfigurasi Otomatis (`POST /api/v1/iot/config`)

Dipanggil saat mesin ESP32 pertama kali terhubung ke WiFi saat booting. Endpoint ini mengirimkan profil nama lembaga/sekolah serta jadwal jam masuk dan jam pulang.

* **URL:** `POST /api/v1/iot/config`
* **Method:** `POST`

### 📥 Request Payload:
```json
{
  "device_id": "DEV-GERBANG-01"
}
```

### 📤 Response Payload (HTTP 200):
```json
{
  "success": true,
  "nama_lembaga": "SDN GINTUNG 1",
  "tipe_sensor": "RFID_2D_BARCODE_COMBO",
  "standby_text": "SILAKAN ABSEN",
  "server_time": "2026-08-30T07:00:00+07:00",
  "schedules": {
    "siswa": {
      "jam_masuk": "07:00",
      "batas_terlambat": "07:30",
      "jam_pulang": "14:00"
    },
    "pegawai": {
      "jam_masuk": "07:30",
      "batas_terlambat": "08:00",
      "jam_pulang": "16:00"
    }
  }
}
```

| Field Response | Keterangan |
|---|---|
| `nama_lembaga` | Nama sekolah / instansi yang ditampilkan pada **Baris 1 LCD Mesin** saat standby. |
| `tipe_sensor` | Varian sensor yang terpasang (`"RFID_MIFARE"`, `"2D_BARCODE"`, `"RFID_2D_BARCODE_COMBO"`). |
| `standby_text` | Pesan standby pada **Baris 2 LCD** (contoh: `"TEMPEL KARTU"`, `"ARAHKAN KODE QR"`). |
| `server_time` | Waktu server saat ini untuk kalibrasi jam RTC mesin. |

---

## 📊 5. Endpoint Live Monitor Stream (`GET /api/v1/iot/monitor/data`)

Endpoint yang diakses secara periodik oleh halaman browser Live Monitor / Layar Smart TV Lobi.

* **URL:** `GET /api/v1/iot/monitor/data` (atau `GET /api/monitor_data.php`)
* **Method:** `GET`

### 📤 Response Payload (HTTP 200):
```json
{
  "records": [
    {
      "id": 105,
      "user_id": 12,
      "nama": "Ahmad Fauzi",
      "inisial": "A",
      "kelas": "XII RPL 1",
      "foto": "https://domain-anda.com/storage/foto/siswa_12.jpg",
      "jam_masuk": "07:15",
      "jam_pulang": "--:--",
      "status": "Hadir",
      "device_id": "DEV-GERBANG-01",
      "time_formatted": "Baru saja",
      "updated_at": "2026-08-30T07:15:22+07:00"
    }
  ],
  "stats": {
    "total_today": 128,
    "hadir": 115,
    "telat": 13,
    "pulang": 0,
    "server_time": "07:15:30",
    "server_date": "Senin, 30 Agustus 2026"
  }
}
```

---

## 🚀 6. Layanan Firmware OTA Terpusat (Centralized OTA)

Mesin Smart Box ESP32 dilengkapi dengan mekanisme pembaruan jarak jauh (*Over-The-Air Update*).

- Pembaruan firmware biner (`.bin`), fitur baru, dan patch keamanan dikelola secara terpusat langsung oleh infrastruktur cloud resmi **`absen.cloud`** (`https://ota.absen.cloud`).
- **Keuntungan bagi Klien:** Server klien **tidak terbebani** oleh hosting file biner firmware, bandwidth download OTA yang besar, ataupun manajemen versi firmware. Mesin akan otomatis memeriksa update ke `absen.cloud` setiap 1 jam secara aman dan transparan.

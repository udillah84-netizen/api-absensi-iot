# 📋 Formulir Checklist Data Pemesanan Unit Hardware (`CLIENT_ORDER_CHECKLIST.md`)

Bagi konsumen atau developer yang memesan unit fisik **Mesin Absensi Smart Box ESP32**, mohon lengkapi dan serahkan formulir parameter berikut kepada kami sebelum proses *flashing firmware* & pengiriman unit:

---

## 📝 Parameter Konfigurasi Firmware Klien

| No | Parameter | Contoh Nilai | Nilai Anda (Isi di sini) |
|---|---|---|---|
| 1 | **Tipe Hardware yang Dipesan** | `RFID Saja` / `Barcode Saja` / `Combo Keduanya` | |
| 2 | **Domain API URL Scan (HTTPS)** | `https://sekolah.sch.id/api/v1/iot/scan` | |
| 3 | **Secret Device Token (`X-Device-Token`)** | `tok_sekolah_rahasia_123` | |
| 4 | **Device ID Label** | `DEV-GERBANG-01` | |
| 5 | **Nama Lokasi Pemasangan** | `Pintu Masuk Gerbang Utama` | |
| 6 | **Default SSID WiFi Sekolah/Kantor** | `WIFI_SEKOLAH_UTAMA` | |
| 7 | **Default Password WiFi** | `rahasia12345` | |

---

## 🔍 Checklist Kesiapan Backend Klien Sebelum Mesin Sampai

- [ ] Skema database tabel `attendances`, `attendance_devices`, dan kolom kartu di master user sudah dibuat.
- [ ] Endpoint `POST /api/v1/iot/scan` sudah dapat menerima request JSON dan mengembalikan response JSON sesuai kontrak.
- [ ] Endpoint `POST /api/v1/iot/heartbeat` sudah aktif.
- [ ] Sudah diuji coba menggunakan **Web Hardware Simulator** di folder `simulator/index.html` atau melalui **Postman Collection**.
- [ ] Domain server sudah menggunakan sertifikat SSL/HTTPS yang valid.

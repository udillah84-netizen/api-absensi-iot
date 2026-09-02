# 🟢 Panduan Integrasi Node.js (Express)

Folder ini berisi implementasi REST API Node.js Express siap pakai untuk mesin absensi IoT ESP32.

---

## ⚡ Cara Menjalankan

1. **Install Dependencies:**
   ```bash
   npm install
   ```

2. **Jalankan Server:**
   ```bash
   npm run dev
   ```

3. **Uji Coba dengan cURL:**
   ```bash
   curl -X POST http://localhost:3000/api/v1/iot/scan \
     -H "Content-Type: application/json" \
     -H "X-Device-Token: tok_rahasia_mesin_anda" \
     -d '{"code": "A1B2C3D4", "device_id": "DEV-GERBANG-01", "jenis": "RFID"}'
   ```

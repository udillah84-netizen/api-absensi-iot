# 🐹 Panduan Integrasi Golang (Gin Framework)

Folder ini berisi implementasi REST API performa tinggi menggunakan Golang & Gin.

---

## ⚡ Cara Menjalankan

1. **Install Dependencies:**
   ```bash
   go mod download
   ```

2. **Jalankan Aplikasi:**
   ```bash
   go run main.go
   ```

3. **Uji Coba dengan cURL:**
   ```bash
   curl -X POST http://localhost:8080/api/v1/iot/scan \
     -H "Content-Type: application/json" \
     -H "X-Device-Token: tok_rahasia_mesin_anda" \
     -d '{"code": "A1B2C3D4", "device_id": "DEV-GERBANG-01"}'
   ```

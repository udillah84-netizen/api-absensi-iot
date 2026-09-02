---
name: golang-gin-iot-attendance-integration
description: Panduan langkah-demi-langkah bagi AI Agent untuk memasang modul integrasi Mesin Absensi IoT ESP32 pada aplikasi Golang berbasis Gin Framework.
---

# Golang Gin IoT Attendance Integration Skill

Gunakan skill ini ketika diminta membuat atau menghubungkan endpoint REST API absensi IoT pada aplikasi backend Golang.

---

## 📋 Langkah Kerja (Execution Workflow)

### 1. Inisialisasi & Download Modul
```bash
go get -u github.com/gin-gonic/gin
```

---

### 2. Salin Handler & Struct
Salin kode dari `backend-examples/golang-gin/main.go` ke dalam package handler proyek Golang Anda.

---

### 3. Daftarkan Rute pada Router Gin
```go
api := r.Group("/api/v1/iot")
api.GET("/ping", PingHandler)

protected := api.Group("/")
protected.Use(AuthMiddleware)
{
    protected.POST("/scan", ScanHandler)
    protected.POST("/heartbeat", HeartbeatHandler)
}
```

---

### 4. Uji Coba Endpoint
```bash
curl -X POST http://localhost:8080/api/v1/iot/scan \
  -H "Content-Type: application/json" \
  -H "X-Device-Token: tok_rahasia_mesin_anda" \
  -d '{"code": "A1B2C3D4", "device_id": "DEV-GERBANG-01"}'
```

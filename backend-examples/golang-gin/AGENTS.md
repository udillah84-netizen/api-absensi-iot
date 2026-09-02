# 🤖 AGENTS.md - Golang (Gin Framework) Integration Guide

Panduan kerja khusus AI Agent saat mengintegrasikan modul **Mesin Absensi IoT ESP32** ke dalam proyek **Golang (Gin / Fiber / Echo)**.

---

## 📌 Konteks & Arsitektur

- **Framework:** Gin Web Framework (`github.com/gin-gonic/gin`)
- **Binding Struct:** `ScanRequest`, `HeartbeatRequest`
- **Auth Handler:** `authMiddleware` via `c.GetHeader("X-Device-Token")`

---

## 🚨 Aturan Kritis Golang yang Harus Dipatuhi

1. **Tag JSON pada Struct**:
   Pastikan struct request menyertakan tag `json:"code" binding:"required"` agar deserialization JSON dari ESP32 berjalan sempurna.
2. **Gunakan Gin JSON Response Helper**:
   Selalu kembalikan respon menggunakan `c.JSON(http.StatusOK, gin.H{...})`.
3. **Format Waktu RFC3339**:
   Saat mengembalikan field `server_time`, gunakan `time.Now().Format(time.RFC3339)` atau ISO-8601 standar agar kompatibel dengan RTC ESP32.

---

## 🛠️ Perintah CLI Utama

```bash
# Download dependencies
go mod download

# Menjalankan server
go run main.go

# Build binary
go build -o server main.go
```

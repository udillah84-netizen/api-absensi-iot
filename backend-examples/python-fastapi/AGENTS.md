# 🤖 AGENTS.md - Python FastAPI Integration Guide

Panduan kerja khusus AI Agent saat mengintegrasikan modul **Mesin Absensi IoT ESP32** ke dalam proyek **Python (FastAPI / Flask / Django REST Framework)**.

---

## 📌 Konteks & Arsitektur

- **Framework:** FastAPI
- **Validasi Data:** Pydantic Models (`ScanPayload`, `HeartbeatPayload`)
- **Server ASGI:** Uvicorn
- **Header Parsing:** `Header(None, alias="X-Device-Token")`

---

## 🚨 Aturan Kritis Python FastAPI yang Harus Dipatuhi

1. **Gunakan Header Alias untuk Menangkap `X-Device-Token`**:
   FastAPI secara otomatis mengonversi `X-Device-Token` menjadi `x_device_token`. Gunakan parameter:
   ```python
   x_device_token: Optional[str] = Header(None, alias="X-Device-Token")
   ```
2. **Respon JSON Sesuai Kontrak**:
   Ketika kartu tidak ditemukan, lemparkan `HTTPException` dengan `status_code=404` dan `detail` berisi objek JSON kontrak, atau kembalikan response dictionary langsung dengan status 404:
   ```python
   raise HTTPException(
       status_code=status.HTTP_404_NOT_FOUND,
       detail={"success": False, "status": "Gagal", "nama": None, "message": "Kartu Tidak Terdaftar"}
   )
   ```
3. **CORS Middleware untuk Pengujian**:
   Pastikan menambahkan `CORSMiddleware` jika simulator browser digunakan untuk menguji server FastAPI lokal.

---

## 🛠️ Perintah CLI Utama

```bash
# Menjalankan server development dengan hot-reload
uvicorn app.main:app --reload --port 8000

# Menjalankan unit tests
pytest
```

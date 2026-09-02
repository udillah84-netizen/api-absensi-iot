---
name: python-fastapi-iot-attendance-integration
description: Panduan langkah-demi-langkah bagi AI Agent untuk memasang modul integrasi Mesin Absensi IoT ESP32 pada aplikasi Python berbasis FastAPI.
---

# Python FastAPI IoT Attendance Integration Skill

Gunakan skill ini ketika diminta membuat atau menghubungkan endpoint REST API absensi IoT pada aplikasi Python (FastAPI).

---

## 📋 Langkah Kerja (Execution Workflow)

### 1. Pasang Dependencies
```bash
pip install fastapi uvicorn pydantic python-dotenv
```

---

### 2. Salin Berkas Aplikasi
Salin skrip dari `backend-examples/python-fastapi/app/main.py` ke dalam direktori modul API proyek FastAPI Anda.

---

### 3. Hubungkan ke Database Proyek
Ganti in-memory `USERS` dictionary dengan query database SQL (SQLAlchemy, SQLModel, Tortoise-ORM, atau Peewee):
```python
user = db.query(User).filter(
    (User.rfid_uid == payload.code) | (User.barcode_uid == payload.code)
).first()
```

---

### 4. Jalankan Server & Uji Coba
```bash
uvicorn app.main:app --reload --port 8000
```
Uji coba endpoint dengan cURL:
```bash
curl -X POST http://localhost:8000/api/v1/iot/scan \
  -H "Content-Type: application/json" \
  -H "X-Device-Token: tok_rahasia_mesin_anda" \
  -d '{"code": "A1B2C3D4", "device_id": "DEV-GERBANG-01"}'
```

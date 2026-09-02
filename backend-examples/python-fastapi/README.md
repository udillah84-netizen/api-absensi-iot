# 🐍 Panduan Integrasi Python (FastAPI)

Folder ini berisi implementasi REST API Python menggunakan FastAPI.

---

## ⚡ Cara Menjalankan

1. **Install Virtual Environment & Requirements:**
   ```bash
   python -m venv venv
   # Di Windows:
   venv\Scripts\activate
   # Di Linux/macOS:
   source venv/bin/activate

   pip install -r requirements.txt
   ```

2. **Jalankan Server Uvicorn:**
   ```bash
   uvicorn app.main:app --reload --port 8000
   ```

3. **Uji Coba dengan cURL:**
   ```bash
   curl -X POST http://localhost:8000/api/v1/iot/scan \
     -H "Content-Type: application/json" \
     -H "X-Device-Token: tok_rahasia_mesin_anda" \
     -d '{"code": "A1B2C3D4", "device_id": "DEV-GERBANG-01"}'
   ```

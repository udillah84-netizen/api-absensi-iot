---
name: nodejs-express-iot-attendance-integration
description: Panduan langkah-demi-langkah bagi AI Agent untuk mengintegrasikan endpoint Mesin Absensi IoT ESP32 pada aplikasi Node.js / Express.
---

# Node.js Express IoT Attendance Integration Skill

Gunakan skill ini ketika diminta memasang atau menghubungkan endpoint REST API absensi IoT pada aplikasi berbasis Node.js Express.

---

## 📋 Langkah Kerja (Execution Workflow)

### 1. Pasang Dependencies
Pastikan proyek Node.js memiliki dependensi dasar:
```bash
npm install express cors dotenv
```

---

### 2. Salin Controller & Middleware
Salin berkas dari `backend-examples/nodejs-express/src/`:
- `controllers/iotController.js`
- `middleware/authMiddleware.js`

---

### 3. Pasang Rute di Server Express
Tambahkan rute pada file utama Express (`app.js` atau `server.js`):

```javascript
const authMiddleware = require('./middleware/authMiddleware');
const iotController = require('./controllers/iotController');

// Proteksi endpoint dengan authMiddleware
app.post('/api/v1/iot/scan', authMiddleware, iotController.scan);
app.post('/api/v1/iot/heartbeat', authMiddleware, iotController.heartbeat);
app.post('/api/v1/iot/config', authMiddleware, iotController.config);
```

---

### 4. Sesuaikan ORM / Database
Ganti mock data di `db.js` dengan query ke database aktual proyek Anda (Prisma, Sequelize, Knex, Mongoose, atau MySQL2 pool).

---

### 5. Uji Coba dengan cURL
```bash
curl -X POST http://localhost:3000/api/v1/iot/scan \
  -H "Content-Type: application/json" \
  -H "X-Device-Token: tok_rahasia_mesin_anda" \
  -d '{"code": "A1B2C3D4", "device_id": "DEV-GERBANG-01"}'
```

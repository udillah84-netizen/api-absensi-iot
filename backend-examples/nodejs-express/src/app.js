require('dotenv').config();
const express = require('express');
const cors = require('cors');
const authMiddleware = require('./middleware/authMiddleware');
const iotController = require('./controllers/iotController');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

// 1. Health check & Ping
app.get('/api/v1/iot/ping', (req, res) => {
  res.json({
    success: true,
    message: 'IoT Express Server is running',
    server_time: new Date().toISOString(),
  });
});

// 2. Proteksi Rute IoT
app.post('/api/v1/iot/scan', authMiddleware, iotController.scan);
app.post('/api/v1/iot/heartbeat', authMiddleware, iotController.heartbeat);
app.post('/api/v1/iot/config', authMiddleware, iotController.config);

app.listen(PORT, () => {
  console.log(`📡 IoT Attendance API Server is listening on http://localhost:${PORT}`);
  console.log(`🔑 Secret Token: ${process.env.IOT_SECRET_TOKEN || 'tok_rahasia_mesin_anda'}`);
});

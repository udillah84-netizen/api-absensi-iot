const db = require('../db');

function authMiddleware(req, res, next) {
  const token = req.headers['x-device-token'] 
    || req.headers['x-iot-token'] 
    || (req.headers.authorization ? req.headers.authorization.replace(/^Bearer\s+/i, '') : null);

  const globalToken = process.env.IOT_SECRET_TOKEN || 'tok_rahasia_mesin_anda';

  if (!token) {
    return res.status(401).json({
      success: false,
      status: 'Unauthorized',
      message: 'Header X-Device-Token is required',
    });
  }

  // 1. Cek Token Global
  if (token === globalToken) {
    return next();
  }

  // 2. Cek Token Device
  const device = db.devices.find((d) => d.token === token);
  if (device) {
    device.status = 'online';
    device.last_heartbeat = new Date();
    req.device = device;
    return next();
  }

  return res.status(401).json({
    success: false,
    status: 'Unauthorized',
    message: 'Invalid Device Token',
  });
}

module.exports = authMiddleware;

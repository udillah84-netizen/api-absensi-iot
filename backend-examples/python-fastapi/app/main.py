import os
from datetime import datetime
from typing import Optional
from fastapi import FastAPI, Header, HTTPException, status
from pydantic import BaseModel

app = FastAPI(title="IoT Attendance API (FastAPI)", version="2.0.0")

IOT_SECRET_TOKEN = os.getenv("IOT_SECRET_TOKEN", "tok_rahasia_mesin_anda")

# In-Memory Mock Database
USERS = {
    "A1B2C3D4": {"id": 1, "name": "Ahmad Fauzi", "role": "siswa", "department": "XII RPL 1", "active": True},
    "E5F6G7H8": {"id": 2, "name": "Budi Santoso", "role": "siswa", "department": "XII RPL 1", "active": True},
    "SISWA-2026-001": {"id": 1, "name": "Ahmad Fauzi", "role": "siswa", "department": "XII RPL 1", "active": True},
}

ATTENDANCES = {}

def format_lcd_name(name: str) -> str:
    if len(name) <= 16:
        return name
    parts = name.split(" ")
    if len(parts) > 1:
        short = parts[0]
        for p in parts[1:]:
            if len(f"{short} {p}") <= 16:
                short = f"{short} {p}"
            else:
                short = f"{short} {p[0]}."
                break
        if len(short) <= 16:
            return short
    return name[:16]

class ScanPayload(BaseModel):
    code: str
    device_id: Optional[str] = "DEV-UNKNOWN"
    scanned_at: Optional[str] = None
    jenis: Optional[str] = "RFID"

class HeartbeatPayload(BaseModel):
    device_id: str
    wifi_rssi: Optional[int] = None
    fw_version: Optional[str] = "v2.0.0"

@app.get("/api/v1/iot/ping")
def ping():
    return {"success": True, "message": "FastAPI IoT Server Running", "server_time": datetime.now().isoformat()}

@app.post("/api/v1/iot/scan")
def process_scan(payload: ScanPayload, x_device_token: Optional[str] = Header(None, alias="X-Device-Token")):
    if x_device_token != IOT_SECRET_TOKEN:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Unauthorized Device Token")

    user = USERS.get(payload.code)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail={"success": False, "status": "Gagal", "nama": None, "message": "Kartu Tidak Terdaftar"}
        )

    now = datetime.now()
    today_str = now.strftime("%Y-%m-%d")
    current_time_str = now.strftime("%H:%M")
    time_hms = now.strftime("%H:%M:%S")
    lcd_name = format_lcd_name(user["name"])

    key = (user["id"], today_str)
    att = ATTENDANCES.get(key)

    # Absen Pulang (jika sudah lewat jam 14:00)
    if att and not att.get("jam_pulang") and time_hms >= "14:00:00":
        att["jam_pulang"] = current_time_str
        return {
            "success": True,
            "status": "Pulang",
            "nama": lcd_name,
            "kelas": user["department"],
            "jam": current_time_str,
            "message": "Absensi Pulang Berhasil",
        }

    # Sudah Absen
    if att and att.get("jam_masuk"):
        return {
            "success": True,
            "status": att["status"],
            "nama": lcd_name,
            "kelas": user["department"],
            "jam": att["jam_masuk"],
            "message": "Sudah Absen Hari Ini",
        }

    # Absen Masuk
    att_status = "Hadir" if time_hms <= "07:30:00" else "Telat"
    ATTENDANCES[key] = {
        "user_id": user["id"],
        "jam_masuk": current_time_str,
        "jam_pulang": None,
        "status": att_status,
    }

    return {
        "success": True,
        "status": att_status,
        "nama": lcd_name,
        "kelas": user["department"],
        "jam": current_time_str,
        "message": "Absensi Masuk Berhasil" if att_status == "Hadir" else "Absen Masuk (Terlambat)",
    }

@app.post("/api/v1/iot/heartbeat")
def heartbeat(payload: HeartbeatPayload, x_device_token: Optional[str] = Header(None, alias="X-Device-Token")):
    if x_device_token != IOT_SECRET_TOKEN:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Unauthorized")
    return {
        "success": True,
        "server_time": datetime.now().isoformat(),
        "heartbeat_interval": 60,
        "message": "Heartbeat OK",
    }

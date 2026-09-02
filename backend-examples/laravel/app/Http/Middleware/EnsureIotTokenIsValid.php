<?php

namespace App\Http\Middleware;

use App\Models\AttendanceDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIotTokenIsValid
{
    /**
     * Handle an incoming request from the ESP32 IoT Attendance Machine.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Mendukung berbagai header token: X-Device-Token, X-IoT-Token, atau Bearer token
        $token = $request->header('X-Device-Token') 
            ?? $request->header('X-IoT-Token') 
            ?? $request->header('x-device-token')
            ?? $request->bearerToken();

        $globalToken = config('iot.secret_token', env('IOT_SECRET_TOKEN'));

        $isValid = false;
        $device = null;

        // 1. Cek kecocokan dengan Global Secret Token
        if (!empty($globalToken) && is_string($token) && hash_equals($globalToken, $token)) {
            $isValid = true;
        }

        // 2. Jika tidak cocok dengan token global, cek token unik per-perangkat di database
        if (!$isValid && !empty($token)) {
            $device = AttendanceDevice::where('token', $token)->first();
            if ($device) {
                $device->updateHeartbeat();
                $request->merge([
                    'iot_device_id' => $device->device_id,
                    'iot_device'    => $device,
                ]);
                $isValid = true;
            }
        }

        // 3. Jika token tidak valid
        if (!$isValid) {
            return response()->json([
                'success' => false,
                'status'  => 'Unauthorized',
                'nama'    => null,
                'message' => 'Token Perangkat Tidak Valid / Unauthorized',
            ], 401);
        }

        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}

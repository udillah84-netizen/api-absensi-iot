<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\AttendanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IotAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected string $secretToken = 'test_secret_token_123';

    protected function setUp(): void
    {
        parent::setUp();
        config(['iot.secret_token' => $this->secretToken]);
    }

    public function test_rejects_unauthorized_request_without_token(): void
    {
        $response = $this->postJson('/api/v1/iot/scan', [
            'code' => 'A1B2C3D4',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'status'  => 'Unauthorized',
            ]);
    }

    public function test_accepts_valid_global_token(): void
    {
        $user = User::factory()->create([
            'name'     => 'Ahmad Fauzi',
            'rfid_uid' => 'A1B2C3D4',
            'role'     => 'siswa',
        ]);

        AttendanceSetting::create([
            'tipe_user'       => 'siswa',
            'jam_masuk'       => '07:00:00',
            'batas_terlambat' => '07:30:00',
            'jam_pulang'      => '14:00:00',
        ]);

        $response = $this->withHeaders([
            'X-Device-Token' => $this->secretToken,
        ])->postJson('/api/v1/iot/scan', [
            'code'       => 'A1B2C3D4',
            'device_id'  => 'DEV-TEST-01',
            'scanned_at' => '2026-08-30T07:15:00+07:00',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status'  => 'Hadir',
                'nama'    => 'Ahmad Fauzi',
            ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status'  => 'Hadir',
        ]);
    }

    public function test_marks_late_when_past_deadline(): void
    {
        $user = User::factory()->create([
            'name'     => 'Budi Santoso',
            'rfid_uid' => 'E5F6G7H8',
            'role'     => 'siswa',
        ]);

        AttendanceSetting::create([
            'tipe_user'       => 'siswa',
            'jam_masuk'       => '07:00:00',
            'batas_terlambat' => '07:30:00',
            'jam_pulang'      => '14:00:00',
        ]);

        $response = $this->withHeaders([
            'X-Device-Token' => $this->secretToken,
        ])->postJson('/api/v1/iot/scan', [
            'code'       => 'E5F6G7H8',
            'scanned_at' => '2026-08-30T07:45:00+07:00', // Lewat 07:30
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status'  => 'Telat',
            ]);
    }

    public function test_returns_404_for_unregistered_card(): void
    {
        $response = $this->withHeaders([
            'X-Device-Token' => $this->secretToken,
        ])->postJson('/api/v1/iot/scan', [
            'code' => 'UNKNOWN9999',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'status'  => 'Gagal',
                'message' => 'Kartu Tidak Terdaftar',
            ]);
    }

    public function test_handles_heartbeat_successfully(): void
    {
        $device = AttendanceDevice::create([
            'device_id' => 'DEV-TEST-01',
            'nama'      => 'Gate Test',
            'token'     => 'device_specific_token',
        ]);

        $response = $this->withHeaders([
            'X-Device-Token' => $this->secretToken,
        ])->postJson('/api/v1/iot/heartbeat', [
            'device_id'  => 'DEV-TEST-01',
            'wifi_rssi'  => -60,
            'fw_version' => 'v2.0.0',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $device->refresh();
        $this->assertEquals('online', $device->status);
    }
}

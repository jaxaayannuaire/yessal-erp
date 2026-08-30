<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Device;
use App\Models\Caisse\DeviceActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceActivityLogFactory extends Factory
{
    protected $model = DeviceActivityLog::class;

    public function definition(): array
    {
        $device = Device::factory()->create();

        return [
            'organization_id' => $device->organization_id,
            'device_id' => $device->id,
            'event_type' => fake()->randomElement([
                'connected',
                'sync_push',
                'sync_rejected',
                'revoked',
                'activated',
            ]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'app_version' => '0.1.0',
            'metadata' => [
                'platform' => fake()->randomElement([
                    'android',
                    'ios',
                    'windows',
                ]),
            ],
            'created_at' => now(),
        ];
    }
}
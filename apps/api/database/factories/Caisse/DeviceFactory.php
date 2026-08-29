<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\Device;
use App\Models\Organization;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        $shop = Shop::factory()->create();

        return [
            'organization_id' => $shop->organization_id,
            'shop_id' => $shop->id,
            'terminal_id' => null,
            'device_uuid' => Str::uuid(),
            'name' => 'Device Test',
            'platform' => 'android',
            'app_version' => '0.1.0',
            'status' => 'active',
            'last_seen_at' => now(),
            'last_sync_at' => null,
            'paired_at' => now(),
            'revoked_at' => null,
        ];
    }
}

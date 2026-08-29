<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\AuditLog;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::query()->value('id') ?? 1,
            'user_id' => null,
            'shop_id' => null,
            'device_id' => null,
            'action' => 'test.action',
            'entity_type' => 'sale',
            'entity_id' => null,
            'before_data' => null,
            'after_data' => null,
            'metadata' => [],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'YessalTest',
            'created_at' => now(),
        ];
    }
}

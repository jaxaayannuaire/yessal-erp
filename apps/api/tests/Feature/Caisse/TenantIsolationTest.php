<?php

namespace Tests\Feature\Caisse;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_organization_header_is_rejected(): void
    {
        $organization = Organization::factory()->create();

        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->withCaisseEntitlement()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Organization-Id', '999999')
            ->getJson('/api/v1/organizations');

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'organization_required',
            ]);
    }
}

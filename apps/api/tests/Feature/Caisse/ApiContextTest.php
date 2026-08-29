<?php

namespace Tests\Feature\Caisse;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiContextTest extends TestCase
{
    use RefreshDatabase;

    private function createOrganizationWithUser(): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        return [$organization, $user];
    }

    public function test_valid_organization_context_is_resolved(): void
    {
        [$organization, $user] = $this->createOrganizationWithUser();

        Sanctum::actingAs($user);

        $response = $this
            ->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/organizations');

        $response
			->assertOk()
			->assertJsonStructure([
				'organizations',
			])
			->assertJsonFragment([
				'id' => $organization->id,
			]);
    }

    public function test_invalid_organization_context_returns_403(): void
    {
        [, $user] = $this->createOrganizationWithUser();

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
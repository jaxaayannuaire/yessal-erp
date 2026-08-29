<?php

namespace Tests\Feature\Caisse;

use App\Models\Entitlement;
use App\Models\Module;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Caisse\EntitlementGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class EntitlementGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_restricted_feature_is_denied_without_entitlement(): void
    {
        $organization = Organization::factory()->create();

        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $this->expectException(AccessDeniedHttpException::class);

        app(EntitlementGate::class)->check(
            $organization,
            'pos.sell'
        );
    }

    public function test_allowed_feature_passes_with_entitlement(): void
    {
        $organization = Organization::factory()->create();

        $plan = Plan::factory()->create();

        Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        $module = Module::create([
            'name' => 'Point de vente',
            'slug' => 'pos',
            'description' => 'Module caisse',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $entitlement = Entitlement::create([
            'name' => 'Vente POS',
            'slug' => 'pos.sell',
            'description' => 'Autorise les ventes POS',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $plan->modules()->attach($module->id);
        $module->entitlements()->attach($entitlement->id);

        app(EntitlementGate::class)->check(
            $organization,
            'pos.sell'
        );

        $this->assertTrue(true);
    }
}
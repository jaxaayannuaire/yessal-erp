<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payments\PaymentService;
use App\Services\Payments\WaveBalanceService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function member(Organization $organization, string $role = 'member'): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => $role]);

        return $user;
    }

    private function organizationAdmin(Organization $organization): User
    {
        $this->seed(RbacSeeder::class);
        $user = $this->member($organization);
        $role = Role::query()->where('slug', 'admin')->firstOrFail();
        $user->organizationRoleAssignments()->create([
            'organization_id' => $organization->id,
            'role_id' => $role->id,
        ]);

        return $user;
    }

    private function authenticate(User $user, Organization $organization): void
    {
        Sanctum::actingAs($user);
        $this->withHeaders([
            'X-Organization-Id' => (string) $organization->id,
        ]);
    }

    private function subscription(Organization $organization): Subscription
    {
        return Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => Plan::factory()->create()->id,
            'status' => 'past_due',
        ]);
    }

    private function payment(Subscription $subscription): Payment
    {
        return app(PaymentService::class)->createPayment(
            $subscription,
            'wave',
            'initial'
        );
    }

    public function test_unauthenticated_user_cannot_create_a_plan(): void
    {
        $this->postJson('/api/v1/plans', [])->assertUnauthorized();
    }

    public function test_normal_user_cannot_create_a_plan(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->member($organization);
        $this->authenticate($user, $organization);

        $this->postJson('/api/v1/plans', [])->assertForbidden();
    }

    public function test_platform_admin_can_manage_plans(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        Sanctum::actingAs($admin);

        $created = $this->postJson('/api/v1/plans', [
            'name' => 'Plan plateforme',
            'price_monthly' => 1000,
            'currency' => 'XOF',
        ])->assertCreated();

        $planId = $created->json('plan.id');
        $this->putJson("/api/v1/plans/{$planId}", ['name' => 'Plan modifié'])
            ->assertOk();
        $this->deleteJson("/api/v1/plans/{$planId}")->assertOk();
    }

    public function test_organization_rbac_admin_is_not_a_platform_admin(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->organizationAdmin($organization);
        $subscription = $this->subscription($organization);
        $payment = $this->payment($subscription);
        $this->authenticate($admin, $organization);

        $this->postJson('/api/v1/plans', [])->assertForbidden();
        $this->getJson('/api/v1/payments/wave/balance')->assertForbidden();
        $this->postJson("/api/v1/subscriptions/{$subscription->id}/activate")->assertForbidden();
        $this->postJson("/api/v1/payments/{$payment->id}/confirm")->assertForbidden();
    }

    public function test_platform_admin_can_access_mocked_wave_balance(): void
    {
        $service = \Mockery::mock(WaveBalanceService::class);
        $service->shouldReceive('getBalance')->once()->andReturn(['balance' => 12345]);
        $this->app->instance(WaveBalanceService::class, $service);

        Sanctum::actingAs(User::factory()->platformAdmin()->create());

        $this->getJson('/api/v1/payments/wave/balance')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance', 12345);
    }

    public function test_platform_admin_can_activate_and_confirm_with_matching_context(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->platformAdmin()->create();
        $organization->users()->attach($admin->id, ['role' => 'member']);
        $subscription = $this->subscription($organization);
        $payment = $this->payment($subscription);
        $this->authenticate($admin, $organization);

        $this->postJson("/api/v1/subscriptions/{$subscription->id}/activate")
            ->assertOk();
        $this->postJson("/api/v1/payments/{$payment->id}/confirm")
            ->assertOk();

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'active']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
    }

    public function test_platform_admin_cannot_mutate_another_organization_context(): void
    {
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        $admin = User::factory()->platformAdmin()->create();
        $organization->users()->attach($admin->id, ['role' => 'member']);
        $otherOrganization->users()->attach($admin->id, ['role' => 'member']);
        $subscription = $this->subscription($otherOrganization);
        $payment = $this->payment($subscription);
        $this->authenticate($admin, $organization);

        $this->postJson("/api/v1/subscriptions/{$subscription->id}/activate")->assertForbidden();
        $this->postJson("/api/v1/payments/{$payment->id}/confirm")->assertForbidden();

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'past_due']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }

    public function test_normal_user_cannot_update_a_plan(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->member($organization);
        $plan = Plan::factory()->create(['name' => 'Plan initial']);
        $this->authenticate($user, $organization);

        $this->putJson("/api/v1/plans/{$plan->id}", ['name' => 'Plan modifié'])
            ->assertForbidden();

        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Plan initial']);
    }

    public function test_normal_user_cannot_delete_a_plan(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->member($organization);
        $plan = Plan::factory()->create();
        $this->authenticate($user, $organization);

        $this->deleteJson("/api/v1/plans/{$plan->id}")->assertForbidden();

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_guest_cannot_access_wave_balance(): void
    {
        $this->getJson('/api/v1/payments/wave/balance')->assertUnauthorized();
    }

    public function test_normal_user_cannot_access_wave_balance(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->member($organization);
        $this->authenticate($user, $organization);

        $this->getJson('/api/v1/payments/wave/balance')->assertForbidden();
    }

    public function test_normal_member_cannot_activate_a_subscription(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->member($organization);
        $subscription = $this->subscription($organization);
        $this->authenticate($member, $organization);

        $this->postJson("/api/v1/subscriptions/{$subscription->id}/activate")
            ->assertForbidden();

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'past_due']);
    }

    public function test_legacy_owner_cannot_activate_own_subscription(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->member($organization, 'owner');
        $subscription = $this->subscription($organization);
        $this->authenticate($owner, $organization);

        $this->postJson("/api/v1/subscriptions/{$subscription->id}/activate")
            ->assertForbidden();
    }

    public function test_cannot_activate_subscription_from_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->member($organization);
        $otherSubscription = $this->subscription(Organization::factory()->create());
        $this->authenticate($member, $organization);

        $this->postJson("/api/v1/subscriptions/{$otherSubscription->id}/activate")
            ->assertForbidden();

        $this->assertDatabaseHas('subscriptions', ['id' => $otherSubscription->id, 'status' => 'past_due']);
    }

    public function test_normal_user_cannot_set_subscription_status_directly(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->member($organization);
        $subscription = $this->subscription($organization);
        $this->authenticate($member, $organization);

        $this->putJson("/api/v1/subscriptions/{$subscription->id}", ['status' => 'active'])
            ->assertForbidden();

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'past_due']);
    }

    public function test_normal_member_cannot_confirm_a_payment(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->member($organization);
        $payment = $this->payment($this->subscription($organization));
        $this->authenticate($member, $organization);

        $this->postJson("/api/v1/payments/{$payment->id}/confirm")
            ->assertForbidden();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }

    public function test_legacy_owner_cannot_confirm_own_payment(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->member($organization, 'owner');
        $payment = $this->payment($this->subscription($organization));
        $this->authenticate($owner, $organization);

        $this->postJson("/api/v1/payments/{$payment->id}/confirm")
            ->assertForbidden();
    }

    public function test_cannot_confirm_payment_from_another_organization(): void
    {
        $organization = Organization::factory()->create();
        $member = $this->member($organization);
        $payment = $this->payment(
            $this->subscription(Organization::factory()->create())
        );
        $this->authenticate($member, $organization);

        $this->postJson("/api/v1/payments/{$payment->id}/confirm")
            ->assertForbidden();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending']);
    }
}

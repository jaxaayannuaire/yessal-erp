<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payments\PaymentService;
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

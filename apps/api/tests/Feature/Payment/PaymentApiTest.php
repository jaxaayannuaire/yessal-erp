<?php

namespace Tests\Feature\Payment;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticate(
        User $user,
        Organization $organization
    ): void {
        Sanctum::actingAs($user);

        $this->withHeaders([
            'X-Organization-Id' => (string) $organization->id,
        ]);
    }

    private function createOrganizationWithUser(): array
    {
        $organization = Organization::factory()->create();

        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $plan = Plan::factory()->create();

        $subscription = Subscription::factory()->create([
            'organization_id' => $organization->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        return [
            $organization,
            $user,
            $plan,
            $subscription,
        ];
    }

    private function createPayment(
        Subscription $subscription
    ) {
        return app(PaymentService::class)->createPayment(
            $subscription,
            'wave',
            'initial'
        );
    }

    public function test_index_returns_only_current_organization_payments(): void
    {
        [
            $organization,
            $user,
            ,
            $subscription,
        ] = $this->createOrganizationWithUser();

        [
            $otherOrganization,
            ,
            ,
            $otherSubscription,
        ] = $this->createOrganizationWithUser();

        $payment = $this->createPayment($subscription);
        $otherPayment = $this->createPayment($otherSubscription);

        $this->authenticate($user, $organization);

        $response = $this->getJson('/api/v1/payments');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $payment->id,
            ])
            ->assertJsonMissing([
                'id' => $otherPayment->id,
            ]);
    }

    public function test_store_rejects_subscription_from_another_organization(): void
    {
        [
            $organization,
            $user,
            ,
        ] = $this->createOrganizationWithUser();

        [
            ,
            ,
            ,
            $otherSubscription,
        ] = $this->createOrganizationWithUser();

        $this->authenticate($user, $organization);

        $response = $this->postJson('/api/v1/payments', [
            'subscription_id' => $otherSubscription->id,
            'payment_method' => 'wave',
            'type' => 'initial',
        ]);

        $response->assertForbidden();
    }

    public function test_show_rejects_payment_from_another_organization(): void
    {
        [
            $organization,
            $user,
            ,
        ] = $this->createOrganizationWithUser();

        [
            ,
            ,
            ,
            $otherSubscription,
        ] = $this->createOrganizationWithUser();

        $payment = $this->createPayment($otherSubscription);

        $this->authenticate($user, $organization);

        $response = $this->getJson(
            "/api/v1/payments/{$payment->id}"
        );

        $response->assertForbidden();
    }

    public function test_confirm_rejects_payment_from_another_organization(): void
    {
        [
            $organization,
            $user,
            ,
        ] = $this->createOrganizationWithUser();

        [
            ,
            ,
            ,
            $otherSubscription,
        ] = $this->createOrganizationWithUser();

        $payment = $this->createPayment($otherSubscription);

        $this->authenticate($user, $organization);

        $response = $this->postJson(
            "/api/v1/payments/{$payment->id}/confirm",
            [
                'provider_transaction_id' => 'TX-OTHER-ORG',
            ]
        );

        $response->assertForbidden();
    }

    public function test_initiate_rejects_payment_from_another_organization(): void
    {
        [
            $organization,
            $user,
            ,
        ] = $this->createOrganizationWithUser();

        [
            ,
            ,
            ,
            $otherSubscription,
        ] = $this->createOrganizationWithUser();

        $payment = $this->createPayment($otherSubscription);

        $this->authenticate($user, $organization);

        $response = $this->postJson(
            "/api/v1/payments/{$payment->id}/initiate"
        );

        $response->assertForbidden();
    }

    public function test_store_creates_payment_for_current_organization(): void
    {
        [
            $organization,
            $user,
            ,
            $subscription,
        ] = $this->createOrganizationWithUser();

        $this->authenticate($user, $organization);

        $response = $this->postJson('/api/v1/payments', [
            'subscription_id' => $subscription->id,
            'payment_method' => 'wave',
            'type' => 'initial',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'payment.subscription_id',
                $subscription->id
            );

        $this->assertDatabaseHas('payments', [
            'subscription_id' => $subscription->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_payments(): void
    {
        $response = $this->getJson('/api/v1/payments');

        $response->assertUnauthorized();
    }
}
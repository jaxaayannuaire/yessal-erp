<?php

namespace Tests\Feature\Subscription;

use App\Models\Subscription;
use App\Services\Subscriptions\SubscriptionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionLifecycleService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SubscriptionLifecycleService::class);
    }

    public function test_un_abonnement_actif_arrive_a_echeance_passe_en_past_due(): void
    {
        config(['app.env' => 'testing']);

        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subMinute(),
            'grace_period_ends_at' => null,
        ]);

        $result = $this->service->markPastDueSubscriptions();

        $subscription->refresh();

        $this->assertSame(1, $result);
        $this->assertSame('past_due', $subscription->status);
        $this->assertNotNull($subscription->grace_period_ends_at);
        $this->assertTrue(
            $subscription->grace_period_ends_at->isFuture()
        );
    }

    public function test_un_abonnement_actif_non_echu_reste_actif(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDay(),
            'grace_period_ends_at' => null,
        ]);

        $result = $this->service->markPastDueSubscriptions();

        $subscription->refresh();

        $this->assertSame(0, $result);
        $this->assertSame('active', $subscription->status);
        $this->assertNull($subscription->grace_period_ends_at);
    }

    public function test_un_abonnement_past_due_reste_past_due_pendant_la_grace(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'past_due',
            'ends_at' => now()->subDay(),
            'grace_period_ends_at' => now()->addDays(2),
        ]);

        $result = $this->service->expirePastDueSubscriptions();

        $subscription->refresh();

        $this->assertSame(0, $result);
        $this->assertSame('past_due', $subscription->status);
    }

    public function test_un_abonnement_past_due_expire_apres_la_grace(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'past_due',
            'ends_at' => now()->subDays(5),
            'grace_period_ends_at' => now()->subMinute(),
        ]);

        $result = $this->service->expirePastDueSubscriptions();

        $subscription->refresh();

        $this->assertSame(1, $result);
        $this->assertSame('expired', $subscription->status);
    }

    public function test_un_abonnement_deja_expire_n_est_pas_modifie(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'expired',
            'ends_at' => now()->subDays(5),
            'grace_period_ends_at' => now()->subDays(2),
        ]);

        $result = $this->service->processExpiredSubscriptions();

        $subscription->refresh();

        $this->assertSame(0, $result['past_due']);
        $this->assertSame(0, $result['expired']);
        $this->assertSame('expired', $subscription->status);
    }

    public function test_un_abonnement_proche_de_lecheance_est_detecte_a_j30(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(15),
        ]);

        $subscriptions = $this->service
            ->findSubscriptionsNeedingRenewal();

        $this->assertTrue(
            $subscriptions->contains('id', $subscription->id)
        );
    }

    public function test_un_abonnement_exactement_a_j30_est_detecte(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(30),
        ]);

        $subscriptions = $this->service
            ->findSubscriptionsNeedingRenewal();

        $this->assertTrue(
            $subscriptions->contains('id', $subscription->id)
        );
    }

    public function test_un_abonnement_au_dela_de_j30_n_est_pas_detecte(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(31),
        ]);

        $subscriptions = $this->service
            ->findSubscriptionsNeedingRenewal();

        $this->assertFalse(
            $subscriptions->contains('id', $subscription->id)
        );
    }

    public function test_un_abonnement_expire_n_est_pas_detecte_pour_renouvellement(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'expired',
            'ends_at' => now()->subDay(),
        ]);

        $subscriptions = $this->service
            ->findSubscriptionsNeedingRenewal();

        $this->assertFalse(
            $subscriptions->contains('id', $subscription->id)
        );
    }

    public function test_un_paiement_de_renouvellement_est_cree(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'price' => 5000,
            'currency' => 'XOF',
            'ends_at' => now()->addDays(10),
        ]);

        $payment = $this->service->createRenewalPayment(
            $subscription,
            'wave'
        );

        $this->assertSame(
            $subscription->id,
            $payment->subscription_id
        );

        $this->assertSame('renewal', $payment->type);
        $this->assertSame('wave', $payment->payment_method);
        $this->assertSame('wave', $payment->provider);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('5000.00', $payment->amount);
    }

    public function test_la_creation_du_renouvellement_est_idempotente(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(10),
        ]);

        $first = $this->service->createRenewalPayment(
            $subscription,
            'wave'
        );

        $second = $this->service->createRenewalPayment(
            $subscription,
            'wave'
        );

        $this->assertSame($first->id, $second->id);

        $this->assertSame(
            1,
            $subscription->payments()
                ->where('type', 'renewal')
                ->count()
        );
    }

    public function test_un_paiement_renewal_pending_exclut_labonnement(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(10),
        ]);

        $this->service->createRenewalPayment(
            $subscription,
            'wave'
        );

        $subscriptions = $this->service
            ->findSubscriptionsNeedingRenewal();

        $this->assertFalse(
            $subscriptions->contains('id', $subscription->id)
        );
    }

    public function test_process_expired_traite_past_due_et_expired(): void
    {
        $active = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subDay(),
            'grace_period_ends_at' => null,
        ]);

        $pastDue = Subscription::factory()->create([
            'status' => 'past_due',
            'ends_at' => now()->subDays(5),
            'grace_period_ends_at' => now()->subMinute(),
        ]);

        $result = $this->service->processExpiredSubscriptions();

        $active->refresh();
        $pastDue->refresh();

        $this->assertSame(1, $result['past_due']);
        $this->assertSame(1, $result['expired']);

        $this->assertSame('past_due', $active->status);
        $this->assertSame('expired', $pastDue->status);
    }

    public function test_process_lifecycle_prepare_les_renouvellements(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(10),
        ]);

        $result = $this->service->processLifecycle();

        $this->assertSame(0, $result['past_due']);
        $this->assertSame(0, $result['expired']);
        $this->assertSame(1, $result['renewal_payments']);

        $this->assertDatabaseHas('payments', [
            'subscription_id' => $subscription->id,
            'type' => 'renewal',
            'payment_method' => 'wave',
            'status' => 'pending',
        ]);
    }

    public function test_process_lifecycle_ne_cree_pas_de_double_renouvellement(): void
    {
        $subscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(10),
        ]);

        $first = $this->service->processLifecycle();

        $second = $this->service->processLifecycle();

        $this->assertSame(1, $first['renewal_payments']);
        $this->assertSame(0, $second['renewal_payments']);

        $this->assertSame(
            1,
            $subscription->payments()
                ->where('type', 'renewal')
                ->count()
        );
    }
}
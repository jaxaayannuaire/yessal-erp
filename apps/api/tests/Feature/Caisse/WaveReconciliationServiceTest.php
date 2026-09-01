<?php

namespace Tests\Feature\Caisse;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Payments\WaveBalanceService;
use App\Services\Payments\WaveReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WaveReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function createSubscription(): Subscription
    {
        return Subscription::factory()->create([
            'price' => 5000,
            'currency' => 'XOF',
        ]);
    }

    private function createPayment(
        Subscription $subscription,
        array $attributes = []
    ): Payment {
        return Payment::create(array_merge([
            'subscription_id' => $subscription->id,
            'amount' => 5000,
            'currency' => 'XOF',
            'payment_method' => 'wave',
            'provider' => 'wave',
            'reference' => 'YES-TEST-001',
            'status' => 'pending',
        ], $attributes));
    }

    public function test_une_transaction_wave_peut_etre_reconciliee_par_identifiant(): void
    {
        $subscription = $this->createSubscription();

        $payment = $this->createPayment(
            $subscription,
            [
                'provider_transaction_id' => 'txn-wave-001',
            ]
        );

        $service = app(WaveReconciliationService::class);

        $result = $service->reconcileTransaction([
            'id' => 'txn-wave-001',
            'amount' => '5000.00',
            'currency' => 'XOF',
        ]);

        $this->assertSame('reconciled', $result['status']);
        $this->assertTrue($result['matched']);
        $this->assertSame($payment->id, $result['payment']->id);
    }

    public function test_une_transaction_wave_peut_etre_reconciliee_par_reference_client(): void
    {
        $subscription = $this->createSubscription();

        $payment = $this->createPayment(
            $subscription,
            [
                'reference' => 'YES-REFERENCE-001',
            ]
        );

        $result = app(WaveReconciliationService::class)
            ->reconcileTransaction([
                'id' => 'txn-wave-002',
                'client_reference' => 'YES-REFERENCE-001',
                'amount' => '5000.00',
                'currency' => 'XOF',
            ]);

        $this->assertSame('reconciled', $result['status']);
        $this->assertTrue($result['matched']);
        $this->assertSame($payment->id, $result['payment']->id);
    }

    public function test_une_transaction_inconnue_est_signalee_unmatched(): void
    {
        $result = app(WaveReconciliationService::class)
            ->reconcileTransaction([
                'id' => 'txn-unknown-001',
                'amount' => '5000.00',
                'currency' => 'XOF',
            ]);

        $this->assertSame('unmatched', $result['status']);
        $this->assertFalse($result['matched']);
        $this->assertNull($result['payment']);
    }

    public function test_une_transaction_sans_identifiant_est_unmatched(): void
    {
        $result = app(WaveReconciliationService::class)
            ->reconcileTransaction([
                'amount' => '5000.00',
                'currency' => 'XOF',
            ]);

        $this->assertSame('unmatched', $result['status']);
        $this->assertFalse($result['matched']);
    }

    public function test_un_montant_different_est_signale(): void
    {
        $subscription = $this->createSubscription();

        $this->createPayment(
            $subscription,
            [
                'provider_transaction_id' => 'txn-amount-mismatch',
            ]
        );

        $result = app(WaveReconciliationService::class)
            ->reconcileTransaction([
                'id' => 'txn-amount-mismatch',
                'amount' => '6000.00',
                'currency' => 'XOF',
            ]);

        $this->assertSame('amount_mismatch', $result['status']);
        $this->assertTrue($result['matched']);
        $this->assertSame('5000.00', $result['expected_amount']);
        $this->assertSame('6000.00', $result['actual_amount']);
    }

    public function test_une_devise_differente_est_signalee(): void
    {
        $subscription = $this->createSubscription();

        $this->createPayment(
            $subscription,
            [
                'provider_transaction_id' => 'txn-currency-mismatch',
            ]
        );

        $result = app(WaveReconciliationService::class)
            ->reconcileTransaction([
                'id' => 'txn-currency-mismatch',
                'amount' => '5000.00',
                'currency' => 'USD',
            ]);

        $this->assertSame('currency_mismatch', $result['status']);
        $this->assertTrue($result['matched']);
        $this->assertSame('XOF', $result['expected_currency']);
        $this->assertSame('USD', $result['actual_currency']);
    }

    public function test_un_paiement_d_un_autre_fournisseur_est_signale(): void
    {
        $subscription = $this->createSubscription();

        $this->createPayment(
            $subscription,
            [
                'provider' => 'orange_money',
                'provider_transaction_id' => 'txn-other-provider',
            ]
        );

        $result = app(WaveReconciliationService::class)
            ->reconcileTransaction([
                'id' => 'txn-other-provider',
                'amount' => '5000.00',
                'currency' => 'XOF',
            ]);

        $this->assertSame('provider_mismatch', $result['status']);
    }

    public function test_plusieurs_transactions_sont_reconciliees(): void
    {
        $subscription = $this->createSubscription();

        $this->createPayment(
            $subscription,
            [
                'provider_transaction_id' => 'txn-ok-001',
            ]
        );

        $this->createPayment(
            $subscription,
            [
                'reference' => 'YES-MISMATCH-001',
                'provider_transaction_id' => 'txn-mismatch-001',
            ]
        );

        $result = app(WaveReconciliationService::class)
            ->reconcileTransactions([
                'items' => [
                    [
                        'id' => 'txn-ok-001',
                        'amount' => '5000.00',
                        'currency' => 'XOF',
                    ],
                    [
                        'id' => 'txn-mismatch-001',
                        'amount' => '7000.00',
                        'currency' => 'XOF',
                    ],
                    [
                        'id' => 'txn-unknown-001',
                        'amount' => '5000.00',
                        'currency' => 'XOF',
                    ],
                ],
                'page_info' => [
                    'has_next_page' => false,
                    'end_cursor' => null,
                ],
            ]);

        $this->assertSame(3, $result['total']);
        $this->assertSame(1, $result['reconciled']);
        $this->assertSame(1, $result['amount_mismatch']);
        $this->assertSame(1, $result['unmatched']);
        $this->assertSame(0, $result['currency_mismatch']);
    }

    public function test_la_reconciliation_ne_modifie_pas_le_paiement(): void
    {
        $subscription = $this->createSubscription();

        $payment = $this->createPayment(
            $subscription,
            [
                'provider_transaction_id' => 'txn-readonly-001',
                'status' => 'pending',
            ]
        );

        $originalMetadata = $payment->metadata;

        app(WaveReconciliationService::class)
            ->reconcileTransaction([
                'id' => 'txn-readonly-001',
                'amount' => '5000.00',
                'currency' => 'XOF',
            ]);

        $payment->refresh();

        $this->assertSame('pending', $payment->status);
        $this->assertSame(
            $originalMetadata,
            $payment->metadata
        );
    }

    public function test_un_paiement_wave_sans_transaction_peut_etre_detecte(): void
    {
        $subscription = $this->createSubscription();

        $payment = $this->createPayment(
            $subscription,
            [
                'provider_transaction_id' => null,
                'status' => 'pending',
            ]
        );

        $payments = app(WaveReconciliationService::class)
            ->findPaymentsMissingTransaction();

        $ids = collect($payments)
            ->map(fn (Payment $item) => $item->id)
            ->all();

        $this->assertContains($payment->id, $ids);
    }

    public function test_reconcile_date_utilise_wave_balance_service(): void
    {
        $mock = Mockery::mock(WaveBalanceService::class);

        $mock->shouldReceive('getTransactions')
            ->once()
            ->with(
                '2026-08-31',
                null,
                false
            )
            ->andReturn([
                'items' => [],
                'page_info' => [
                    'has_next_page' => false,
                    'end_cursor' => null,
                ],
            ]);

        $this->app->instance(
            WaveBalanceService::class,
            $mock
        );

        $result = app(WaveReconciliationService::class)
            ->reconcileDate('2026-08-31');

        $this->assertSame(0, $result['total']);
        $this->assertSame(0, $result['reconciled']);
    }

    public function test_reconcile_page_transmet_le_cursor(): void
    {
        $mock = Mockery::mock(WaveBalanceService::class);

        $mock->shouldReceive('getTransactions')
            ->once()
            ->with(
                '2026-08-31',
                'cursor-test-001',
                true
            )
            ->andReturn([
                'items' => [],
                'page_info' => [
                    'has_next_page' => true,
                    'end_cursor' => 'cursor-test-002',
                ],
            ]);

        $this->app->instance(
            WaveBalanceService::class,
            $mock
        );

        $result = app(WaveReconciliationService::class)
            ->reconcilePage(
                '2026-08-31',
                'cursor-test-001',
                true
            );

        $this->assertSame(0, $result['total']);
        $this->assertTrue(
            $result['page_info']['has_next_page']
        );
        $this->assertSame(
            'cursor-test-002',
            $result['page_info']['end_cursor']
        );
    }
}
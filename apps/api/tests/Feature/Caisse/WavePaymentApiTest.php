<?php

namespace Tests\Feature\Caisse;

use App\Models\Subscription;
use App\Services\Payments\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WavePaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private function createPayment(string $type = 'initial'): array
    {
        $subscription = Subscription::factory()->create();

        $payment = app(PaymentService::class)->createPayment(
            $subscription,
            'wave',
            $type
        );

        return [$subscription, $payment];
    }

    private function signedWebhook(array $payload, ?int $timestamp = null)
    {
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp ??= time();

        $signature = hash_hmac(
            'sha256',
            $timestamp . $rawBody,
            config('services.wave.webhook_secret')
        );

        return $this->call(
            'POST',
            '/api/v1/payments/wave/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_WAVE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            ],
            $rawBody
        );
    }

    public function test_le_checkout_wave_peut_etre_initie(): void
    {
        [, $payment] = $this->createPayment();

        Http::fake([
            'https://api.wave.com/v1/checkout/sessions' => Http::response([
                'id' => 'cos-test-001',
                'amount' => '5000.00',
                'checkout_status' => 'open',
                'client_reference' => $payment->reference,
                'currency' => 'XOF',
                'wave_launch_url' => 'https://pay.wave.com/c/cos-test-001',
            ], 200),
        ]);

        $result = app(PaymentService::class)->initiatePayment($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('initiated', $result['status']);
        $this->assertSame($payment->id, $result['payment_id']);
        $this->assertSame($payment->reference, $result['reference']);
        $this->assertSame('cos-test-001', $result['provider_transaction_id']);
        $this->assertSame(
            'https://pay.wave.com/c/cos-test-001',
            $result['checkout_url']
        );

        $payment->refresh();

        $this->assertSame(
            'cos-test-001',
            $payment->provider_transaction_id
        );
        $this->assertSame(
            $payment->reference,
            $payment->metadata['wave_session']['client_reference']
        );

        Http::assertSent(function ($request) use ($payment) {
            return $request->url()
                === 'https://api.wave.com/v1/checkout/sessions'
                && $request['amount'] === '5000.00'
                && $request['currency'] === 'XOF'
                && $request['client_reference'] === $payment->reference;
        });
    }

    public function test_une_erreur_du_fournisseur_wave_est_correctement_geree(): void
    {
        [, $payment] = $this->createPayment();

        Http::fake([
            'https://api.wave.com/v1/checkout/sessions' => Http::response([
                'code' => 'test-error',
                'message' => 'Provider error',
            ], 500),
        ]);

        $result = app(PaymentService::class)->initiatePayment($payment);

        $this->assertFalse($result['success']);
        $this->assertSame('provider_error', $result['status']);
        $this->assertSame($payment->id, $result['payment_id']);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_le_webhook_wave_signe_confirme_le_paiement(): void
    {
        [$subscription, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-completed',
        ]);

        $response = $this->signedWebhook([
            'type' => 'checkout.session.completed',
            'data' => [
                'id' => 'cos-test-completed',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'received' => true,
                'processed' => true,
                'event' => 'checkout.session.completed',
                'payment_id' => $payment->id,
                'status' => 'paid',
            ]);

        $payment->refresh();
        $subscription->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('active', $subscription->status);
    }

    public function test_le_webhook_wave_signe_marque_le_paiement_comme_echoue(): void
    {
        [$subscription, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-failed',
        ]);

        $response = $this->signedWebhook([
            'type' => 'checkout.session.failed',
            'data' => [
                'id' => 'cos-test-failed',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'received' => true,
                'processed' => true,
                'event' => 'checkout.session.failed',
                'payment_id' => $payment->id,
                'status' => 'failed',
            ]);

        $payment->refresh();
        $subscription->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('past_due', $subscription->status);
        $this->assertNull($payment->paid_at);
    }

    public function test_le_webhook_wave_signe_annule_le_paiement(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-cancelled',
        ]);

        $response = $this->signedWebhook([
            'type' => 'checkout.session.cancelled',
            'data' => [
                'id' => 'cos-test-cancelled',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'processed' => true,
                'event' => 'checkout.session.cancelled',
                'payment_id' => $payment->id,
                'status' => 'cancelled',
            ]);

        $this->assertSame('cancelled', $payment->fresh()->status);
    }

    public function test_le_webhook_wave_signe_expire_le_paiement(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-expired',
        ]);

        $response = $this->signedWebhook([
            'type' => 'checkout.session.expired',
            'data' => [
                'id' => 'cos-test-expired',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'processed' => true,
                'event' => 'checkout.session.expired',
                'payment_id' => $payment->id,
                'status' => 'expired',
            ]);

        $this->assertSame('expired', $payment->fresh()->status);
    }

    public function test_une_signature_wave_invalide_est_rejetee(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-invalid-signature',
        ]);

        $response = $this->postJson(
            '/api/v1/payments/wave/webhook',
            [
                'type' => 'checkout.session.completed',
                'data' => [
                    'id' => 'cos-test-invalid-signature',
                ],
            ],
            [
                'Wave-Signature' => 't=' . time() . ',v1=invalid',
            ]
        );

        $response->assertUnauthorized();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_webhook_wave_expire_est_rejete(): void
    {
        $response = $this->signedWebhook([
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cos-test-expired-webhook'],
        ], time() - 301);

        $response->assertUnauthorized();
    }

    public function test_un_webhook_wave_ne_modifie_pas_un_paiement_d_un_autre_provider(): void
    {
        [, $payment] = $this->createPayment();
        $payment->update([
            'provider' => 'other',
            'provider_transaction_id' => 'cos-test-other-provider',
        ]);

        $response = $this->signedWebhook([
            'type' => 'checkout.session.completed',
            'data' => ['id' => 'cos-test-other-provider'],
        ]);

        $response->assertOk()->assertJsonPath('processed', false);
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_evenement_wave_sans_signature_est_rejete(): void
    {
        $response = $this->postJson(
            '/api/v1/payments/wave/webhook',
            [
                'type' => 'checkout.session.completed',
                'data' => [
                    'id' => 'cos-test-no-signature',
                ],
            ]
        );

        $response->assertUnauthorized();
    }

    public function test_le_healthcheck_wave_sans_signature_est_accepte(): void
    {
        $response = $this->postJson(
            '/api/v1/payments/wave/webhook',
            [
                'type' => 'healthcheck',
            ]
        );

        $response->assertOk()
            ->assertJson([
                'received' => true,
                'processed' => false,
                'event' => 'healthcheck',
            ]);
    }

    public function test_un_evenement_wave_inconnu_est_ignore(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-unknown-event',
        ]);

        $response = $this->signedWebhook([
            'type' => 'checkout.session.unknown',
            'data' => [
                'id' => 'cos-test-unknown-event',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'received' => true,
                'processed' => false,
                'event' => 'checkout.session.unknown',
            ]);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_un_paiement_wave_inexistant_n_est_pas_traite(): void
    {
        $response = $this->signedWebhook([
            'type' => 'checkout.session.completed',
            'data' => [
                'id' => 'cos-test-not-found',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'received' => true,
                'processed' => false,
                'message' => 'Paiement introuvable.',
            ]);
    }

    public function test_un_webhook_completed_est_sans_effet_sur_un_paiement_deja_paye(): void
    {
        [$subscription, $payment] = $this->createPayment();

        $paidAt = now()->subMinute();

        $payment->update([
            'provider_transaction_id' => 'cos-test-already-paid',
            'status' => 'paid',
            'paid_at' => $paidAt,
        ]);

        $subscription->update([
            'status' => 'active',
        ]);

        $response = $this->signedWebhook([
            'type' => 'checkout.session.completed',
            'data' => [
                'id' => 'cos-test-already-paid',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'processed' => true,
                'status' => 'paid',
            ]);

        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertEquals(
            $paidAt->timestamp,
            $payment->paid_at->timestamp
        );
    }

    public function test_un_webhook_failed_repete_conserve_le_meme_etat_final(): void
    {
        [$subscription, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-failed-idempotent',
        ]);

        $payload = [
            'type' => 'checkout.session.failed',
            'data' => [
                'id' => 'cos-test-failed-idempotent',
            ],
        ];

        $this->signedWebhook($payload)->assertOk();

        $payment->refresh();
        $firstMetadata = $payment->metadata;

        $this->signedWebhook($payload)->assertOk();

        $payment->refresh();
        $subscription->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('past_due', $subscription->status);
        $this->assertEquals($firstMetadata, $payment->metadata);
    }

    public function test_un_webhook_completed_de_renouvellement_reactive_et_prolonge_l_abonnement(): void
    {
        [$subscription, $payment] = $this->createPayment('renewal');

        $subscription->update([
            'status' => 'expired',
            'billing_cycle' => 'monthly',
            'ends_at' => now()->subDay(),
        ]);

        $payment->update([
            'provider_transaction_id' => 'cos-test-renewal',
        ]);

        $response = $this->signedWebhook([
            'type' => 'checkout.session.completed',
            'data' => [
                'id' => 'cos-test-renewal',
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'processed' => true,
                'status' => 'paid',
            ]);

        $payment->refresh();
        $subscription->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    public function test_un_paiement_deja_paye_n_est_pas_reinitie(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $result = app(PaymentService::class)->initiatePayment($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('already_paid', $result['status']);
        $this->assertSame($payment->id, $result['payment_id']);
    }

    public function test_la_verification_wave_sans_identifiant_de_transaction_echoue(): void
    {
        [, $payment] = $this->createPayment();

        $result = app(PaymentService::class)
            ->getProvider('wave')
            ->verify($payment);

        $this->assertFalse($result['success']);
        $this->assertSame('missing_transaction_id', $result['status']);
        $this->assertSame($payment->id, $result['payment_id']);
    }

    public function test_la_verification_wave_retourne_le_statut_paid(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-verify-paid',
        ]);

        Http::fake([
            'https://api.wave.com/v1/checkout/sessions*' => Http::response([
                'id' => 'cos-test-verify-paid',
                'payment_status' => 'succeeded',
                'checkout_status' => 'complete',
                'client_reference' => $payment->reference,
                'currency' => 'XOF',
                'amount' => '5000.00',
            ], 200),
        ]);

        $result = app(PaymentService::class)
            ->getProvider('wave')
            ->verify($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('paid', $result['status']);
        $this->assertSame('succeeded', $result['payment_status']);
        $this->assertSame('complete', $result['checkout_status']);
        $this->assertSame(
            'cos-test-verify-paid',
            $result['provider_transaction_id']
        );
    }

    public function test_la_verification_wave_retourne_le_statut_pending(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-verify-pending',
        ]);

        Http::fake([
            'https://api.wave.com/v1/checkout/sessions*' => Http::response([
                'id' => 'cos-test-verify-pending',
                'payment_status' => 'processing',
                'checkout_status' => 'open',
                'client_reference' => $payment->reference,
                'currency' => 'XOF',
                'amount' => '5000.00',
            ], 200),
        ]);

        $result = app(PaymentService::class)
            ->getProvider('wave')
            ->verify($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('pending', $result['status']);
        $this->assertSame('processing', $result['payment_status']);
        $this->assertSame('open', $result['checkout_status']);
    }

    public function test_la_verification_wave_retourne_le_statut_failed(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-verify-failed',
        ]);

        Http::fake([
            'https://api.wave.com/v1/checkout/sessions*' => Http::response([
                'id' => 'cos-test-verify-failed',
                'payment_status' => 'failed',
                'checkout_status' => 'closed',
                'client_reference' => $payment->reference,
                'currency' => 'XOF',
                'amount' => '5000.00',
            ], 200),
        ]);

        $result = app(PaymentService::class)
            ->getProvider('wave')
            ->verify($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('failed', $result['status']);
        $this->assertSame('failed', $result['payment_status']);
    }

    public function test_la_verification_wave_retourne_le_statut_cancelled(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-verify-cancelled',
        ]);

        Http::fake([
            'https://api.wave.com/v1/checkout/sessions*' => Http::response([
                'id' => 'cos-test-verify-cancelled',
                'payment_status' => 'cancelled',
                'checkout_status' => 'closed',
                'client_reference' => $payment->reference,
                'currency' => 'XOF',
                'amount' => '5000.00',
            ], 200),
        ]);

        $result = app(PaymentService::class)
            ->getProvider('wave')
            ->verify($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('cancelled', $result['status']);
        $this->assertSame('cancelled', $result['payment_status']);
    }

    public function test_la_verification_wave_retourne_le_statut_expired(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-verify-expired',
        ]);

        Http::fake([
            'https://api.wave.com/v1/checkout/sessions*' => Http::response([
                'id' => 'cos-test-verify-expired',
                'payment_status' => 'expired',
                'checkout_status' => 'closed',
                'client_reference' => $payment->reference,
                'currency' => 'XOF',
                'amount' => '5000.00',
            ], 200),
        ]);

        $result = app(PaymentService::class)
            ->getProvider('wave')
            ->verify($payment);

        $this->assertTrue($result['success']);
        $this->assertSame('expired', $result['status']);
        $this->assertSame('expired', $result['payment_status']);
    }

    public function test_une_erreur_du_fournisseur_est_geree_lors_de_la_verification_wave(): void
    {
        [, $payment] = $this->createPayment();

        $payment->update([
            'provider_transaction_id' => 'cos-test-verify-error',
        ]);

        Http::fake([
            'https://api.wave.com/v1/checkout/sessions*' => Http::response([
                'code' => 'test-error',
                'message' => 'Provider error',
            ], 500),
        ]);

        $result = app(PaymentService::class)
            ->getProvider('wave')
            ->verify($payment);

        $this->assertFalse($result['success']);
        $this->assertSame('provider_error', $result['status']);
        $this->assertSame($payment->id, $result['payment_id']);
    }
}

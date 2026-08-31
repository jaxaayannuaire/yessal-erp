<?php

namespace Tests\Feature\Caisse;

use App\Models\Payment;
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

    private function signedWebhook(array $payload)
    {
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();

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

    public function test_wave_checkout_can_be_initiated(): void
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

    public function test_wave_checkout_provider_error_is_handled(): void
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

    public function test_signed_completed_webhook_confirms_payment(): void
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

    public function test_signed_failed_webhook_marks_payment_failed(): void
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

    public function test_signed_cancelled_webhook_marks_payment_cancelled(): void
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

    public function test_signed_expired_webhook_marks_payment_expired(): void
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

    public function test_invalid_wave_signature_is_rejected(): void
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

    public function test_real_wave_event_without_signature_is_rejected(): void
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

    public function test_healthcheck_without_signature_is_accepted(): void
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

    public function test_unknown_event_is_ignored(): void
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

    public function test_unknown_wave_payment_is_not_processed(): void
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

    public function test_completed_webhook_is_safe_when_payment_is_already_paid(): void
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

    public function test_failed_webhook_can_be_repeated_without_changing_final_state(): void
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

        $firstResponse = $this->signedWebhook($payload);

        $firstResponse->assertOk();

        $payment->refresh();

        $firstMetadata = $payment->metadata;

        $secondResponse = $this->signedWebhook($payload);

        $secondResponse->assertOk();

        $payment->refresh();
        $subscription->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('past_due', $subscription->status);
        $this->assertEquals($firstMetadata, $payment->metadata);
    }

    public function test_renewal_completed_webhook_reactivates_and_extends_subscription(): void
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

    public function test_payment_service_returns_already_paid_for_paid_payment(): void
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

    public function test_wave_verify_is_explicitly_not_implemented(): void
    {
        [, $payment] = $this->createPayment();

        $result = app(PaymentService::class)
            ->getProvider('wave')
            ->verify($payment);

        $this->assertFalse($result['success']);
        $this->assertSame('not_implemented', $result['status']);
    }
}
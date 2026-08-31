<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WavePaymentProvider implements PaymentProviderInterface
{
    public function initiate(
        Payment $payment,
        array $data = []
    ): array {
        try {
            $response = Http::withToken(
                config('services.wave.api_key')
            )
                ->acceptJson()
                ->post(
                    rtrim(
                        config('services.wave.api_url'),
                        '/'
                    ) . '/checkout/sessions',
                    [
                        'amount' => (string) $payment->amount,
                        'currency' => $payment->currency,
                        'success_url' => config(
                            'services.wave.success_url'
                        ),
                        'error_url' => config(
                            'services.wave.error_url'
                        ),
                        'client_reference' => $payment->reference,
                    ]
                );

            if ($response->failed()) {
                Log::error(
                    'Wave checkout initiation failed',
                    [
                        'payment_id' => $payment->id,
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]
                );

                return [
                    'success' => false,
                    'status' => 'provider_error',
                    'message' => 'Impossible de créer la session Wave.',
                    'payment_id' => $payment->id,
                ];
            }

            $waveSession = $response->json();

            $payment->update([
                'provider' => 'wave',
                'provider_transaction_id' =>
                    $waveSession['id'] ?? null,
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'wave_session' => $waveSession,
                    ]
                ),
            ]);

            return [
                'success' => true,
                'status' => 'initiated',
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'provider_transaction_id' =>
                    $waveSession['id'] ?? null,
                'checkout_url' =>
                    $waveSession['wave_launch_url'] ?? null,
            ];
        } catch (\Throwable $exception) {
            Log::error(
                'Wave payment exception',
                [
                    'payment_id' => $payment->id,
                    'message' => $exception->getMessage(),
                ]
            );

            return [
                'success' => false,
                'status' => 'exception',
                'message' => 'Erreur lors de la connexion à Wave.',
                'payment_id' => $payment->id,
            ];
        }
    }

    public function verify(
        Payment $payment,
        array $data = []
    ): array {
        $transactionId = $payment->provider_transaction_id;

        if (!$transactionId) {
            return [
                'success' => false,
                'status' => 'missing_transaction_id',
                'message' => 'Identifiant de transaction Wave absent.',
                'payment_id' => $payment->id,
            ];
        }

        try {
            $response = Http::withToken(
                config('services.wave.api_key')
            )
                ->acceptJson()
                ->get(
                    rtrim(
                        config('services.wave.api_url'),
                        '/'
                    ) . '/checkout/sessions',
                    [
                        'transaction_id' => $transactionId,
                    ]
                );

            if ($response->failed()) {
                Log::error(
                    'Wave checkout verification failed',
                    [
                        'payment_id' => $payment->id,
                        'provider_transaction_id' => $transactionId,
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]
                );

                return [
                    'success' => false,
                    'status' => 'provider_error',
                    'message' => 'Impossible de vérifier la session Wave.',
                    'payment_id' => $payment->id,
                ];
            }

            $result = $response->json();

            $session = $result['data'][0] ?? $result;

            $paymentStatus = $session['payment_status'] ?? null;
            $checkoutStatus = $session['checkout_status'] ?? null;

            $status = match ($paymentStatus) {
                'succeeded', 'completed' => 'paid',
                'failed' => 'failed',
                'cancelled', 'canceled' => 'cancelled',
                'expired' => 'expired',
                default => 'pending',
            };

            return [
                'success' => true,
                'status' => $status,
                'payment_id' => $payment->id,
                'provider_transaction_id' => $transactionId,
                'payment_status' => $paymentStatus,
                'checkout_status' => $checkoutStatus,
                'session' => $session,
            ];
        } catch (\Throwable $exception) {
            Log::error(
                'Wave payment verification exception',
                [
                    'payment_id' => $payment->id,
                    'provider_transaction_id' => $transactionId,
                    'message' => $exception->getMessage(),
                ]
            );

            return [
                'success' => false,
                'status' => 'exception',
                'message' => 'Erreur lors de la vérification Wave.',
                'payment_id' => $payment->id,
            ];
        }
    }
}
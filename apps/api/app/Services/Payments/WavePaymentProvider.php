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
        return [
            'success' => false,
            'status' => 'not_implemented',
            'message' => 'Vérification Wave non implémentée.',
            'payment_id' => $payment->id,
        ];
    }
}
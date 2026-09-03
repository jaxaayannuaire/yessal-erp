<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Organization;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private function currentOrganization(Request $request): Organization
    {
        return $request->attributes->get('currentOrganization');
    }

    private function ensureOwnership(
        Request $request,
        Payment $payment
    ): void {
        $payment->loadMissing('subscription');

        abort_unless(
            $payment->subscription
                && (int) $payment->subscription->organization_id
                    === (int) $this->currentOrganization($request)->id,
            403,
            'Paiement inaccessible.'
        );
    }

    private function ensureSubscriptionOwnership(
        Request $request,
        Subscription $subscription
    ): void {
        abort_unless(
            (int) $subscription->organization_id
                === (int) $this->currentOrganization($request)->id,
            403,
            'Souscription inaccessible.'
        );
    }
        public function index(Request $request)
    {
        $organization = $this->currentOrganization($request);

        return response()->json([
            'payments' => Payment::whereHas(
                'subscription',
                fn ($query) => $query->where(
                    'organization_id',
                    $organization->id
                )
            )
                ->with([
                    'subscription.organization',
                    'subscription.plan',
                ])
                ->latest()
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        PaymentService $paymentService
    ) {
        $validated = $request->validate([
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'payment_method' => [
                'required',
                'in:wave,orange_money,free_money,paytech,manual',
            ],
            'type' => [
                'nullable',
                'in:initial,renewal,manual',
            ],
        ]);

        $subscription = Subscription::findOrFail(
            $validated['subscription_id']
        );
        $this->ensureSubscriptionOwnership(
            $request,
            $subscription
        );

        $payment = $paymentService->createPayment(
            $subscription,
            $validated['payment_method'],
            $validated['type'] ?? 'initial'
        );

        return response()->json([
            'message' => 'Paiement créé avec succès.',
            'payment' => $payment->load([
                'subscription.organization',
                'subscription.plan',
            ]),
        ], 201);
    }

    public function show(Request $request, Payment $payment)
    {
        $this->ensureOwnership($request, $payment);

        return response()->json([
            'payment' => $payment->load([
                'subscription.organization',
                'subscription.plan',
            ]),
        ]);
    }

    public function confirm(
        Request $request,
        Payment $payment,
        PaymentService $paymentService
    ) {
        $this->ensureOwnership($request, $payment);

        abort(403, 'La confirmation d’un paiement est réservée à la plateforme.');

        $validated = $request->validate([
            'provider_transaction_id' => [
                'nullable',
                'string',
                'max:255',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ]);

        $payment = $paymentService->confirmPayment(
            $payment,
            $validated['provider_transaction_id'] ?? null,
            $validated['metadata'] ?? null
        );

        return response()->json([
            'message' => 'Paiement confirmé et souscription activée avec succès.',
            'payment' => $payment->load([
                'subscription.organization',
                'subscription.plan',
            ]),
        ]);
    }

    public function initiate(
        Request $request,
        Payment $payment,
        PaymentService $paymentService
    ) {
        $this->ensureOwnership($request, $payment);
        return response()->json(
            $paymentService->initiatePayment($payment)
        );
    }

    public function waveWebhook(
        Request $request,
        PaymentService $paymentService
    ) {
        $rawBody = $request->getContent();
        $signature = $request->header('Wave-Signature');
        $payload = json_decode($rawBody, true);

        Log::info('Wave webhook request arrived', [
            'signature_present' => !empty($signature),
            'raw_body' => $rawBody,
        ]);

        if (!is_array($payload)) {
            return response()->json([
                'message' => 'Payload Wave invalide.',
            ], 422);
        }

        $event = $payload['type']
            ?? $payload['event']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | 1. Validation de la signature
        |--------------------------------------------------------------------------
        */

        if ($signature) {
            $parts = [];

            foreach (explode(',', $signature) as $part) {
                [$key, $value] = array_pad(
                    explode('=', trim($part), 2),
                    2,
                    null
                );

                if ($key !== null && $value !== null) {
                    $parts[trim($key)] = trim($value);
                }
            }

            $timestamp = $parts['t'] ?? null;
            $receivedSignature = $parts['v1'] ?? null;

            if (
                $timestamp === null ||
                $receivedSignature === null
            ) {
                Log::warning(
                    'Wave webhook signature format invalid.',
                    [
                        'signature' => $signature,
                    ]
                );

                return response()->json([
                    'message' => 'Signature Wave invalide.',
                ], 401);
            }

            $secret = config(
                'services.wave.webhook_secret'
            );

            if (empty($secret)) {
                Log::error(
                    'Wave webhook secret missing.'
                );

                return response()->json([
                    'message' => 'Configuration webhook invalide.',
                ], 500);
            }

            $expectedSignature = hash_hmac(
                'sha256',
                $timestamp . $rawBody,
                $secret
            );

            if (!hash_equals(
                strtolower($expectedSignature),
                strtolower($receivedSignature)
            )) {
                Log::warning(
                    'Wave webhook signature invalid.',
                    [
                        'event' => $event,
                    ]
                );

                return response()->json([
                    'message' => 'Signature Wave invalide.',
                ], 401);
            }

            Log::info(
                'Wave webhook signature validated.',
                [
                    'event' => $event,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Tests Wave sans signature
        |--------------------------------------------------------------------------
        */

        if (
            !$signature &&
            in_array(
                $event,
                ['healthcheck', 'test.test_event'],
                true
            )
        ) {
            Log::info(
                'Wave test/healthcheck accepted',
                [
                    'event' => $event,
                    'payload' => $payload,
                ]
            );

            return response()->json([
                'received' => true,
                'processed' => false,
                'event' => $event,
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Signature obligatoire pour les événements réels
        |--------------------------------------------------------------------------
        */

        if (!$signature) {
            Log::warning(
                'Wave webhook signature missing.',
                [
                    'event' => $event,
                ]
            );

            return response()->json([
                'message' => 'Signature Wave manquante.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Recherche du paiement
        |--------------------------------------------------------------------------
        */

        $waveData = $payload['data'] ?? $payload;

        $transactionId =
            $waveData['id']
            ?? $waveData['checkout_session_id']
            ?? $waveData['session_id']
            ?? null;

        $payment = $transactionId
            ? $paymentService->findByProviderTransactionId(
                $transactionId
            )
            : null;

        if (!$payment) {
            Log::warning(
                'Wave payment not found.',
                [
                    'event' => $event,
                    'transaction_id' => $transactionId,
                ]
            );

            return response()->json([
                'received' => true,
                'processed' => false,
                'message' => 'Paiement introuvable.',
            ], 200);
        }

        $metadata = [
            'wave_webhook_event' => $event,
            'wave_webhook_payload' => $payload,
        ];

        /*
        |--------------------------------------------------------------------------
        | 5. Traitement de l'événement
        |--------------------------------------------------------------------------
        */

        switch ($event) {
            case 'checkout.session.completed':

                $payment = $paymentService->confirmPayment(
                    $payment,
                    $transactionId,
                    $metadata
                );

                break;

            case 'checkout.session.failed':

                $payment = $paymentService->updatePaymentStatus(
                    $payment,
                    'failed',
                    $metadata
                );

                break;

            case 'checkout.session.cancelled':

                $payment = $paymentService->updatePaymentStatus(
                    $payment,
                    'cancelled',
                    $metadata
                );

                break;

            case 'checkout.session.expired':

                $payment = $paymentService->updatePaymentStatus(
                    $payment,
                    'expired',
                    $metadata
                );

                break;

            default:

                Log::info(
                    'Wave webhook event ignored.',
                    [
                        'event' => $event,
                    ]
                );

                return response()->json([
                    'received' => true,
                    'processed' => false,
                    'event' => $event,
                ], 200);
        }

        return response()->json([
            'received' => true,
            'processed' => true,
            'event' => $event,
            'payment_id' => $payment->id,
            'status' => $payment->status,
        ], 200);
    }

    public function waveBalance(
        \App\Services\Payments\WaveBalanceService $waveBalanceService
    ) {
        abort(403, 'La consultation du solde Wave est réservée à la plateforme.');

        return response()->json([
            'success' => true,
            'data' => $waveBalanceService->getBalance(),
        ]);
    }
}

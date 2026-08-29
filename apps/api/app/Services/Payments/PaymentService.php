<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(
        Subscription $subscription,
        string $paymentMethod,
        string $type = 'initial'
    ): Payment {
        $existingPayment = Payment::where(
            'subscription_id',
            $subscription->id
        )
            ->where('status', 'pending')
            ->where('type', $type)
            ->first();

        if ($existingPayment) {
            return $existingPayment;
        }

        return Payment::create([
            'subscription_id' => $subscription->id,
            'amount' => $subscription->price,
            'currency' => $subscription->currency ?? 'XOF',
            'payment_method' => $paymentMethod,
            'type' => $type,
            'provider' => $paymentMethod,
            'reference' => 'YES-' . strtoupper(Str::random(16)),
            'status' => 'pending',
        ]);
    }

    public function confirmPayment(
        Payment $payment,
        ?string $transactionId = null,
        ?array $metadata = null
    ): Payment {
        if ($payment->status === 'paid') {
            return $payment->fresh(['subscription']);
        }

        $currentMetadata = $payment->metadata ?? [];

        $payment->update([
            'status' => 'paid',
            'provider_transaction_id' =>
                $transactionId ?? $payment->provider_transaction_id,
            'metadata' => array_merge(
                $currentMetadata,
                $metadata ?? []
            ),
            'paid_at' => now(),
        ]);

        $subscription = $payment->subscription;

		if ($payment->type === 'renewal') {
			$cycle = $subscription->billing_cycle ?? 'monthly';

			$baseDate = $subscription->ends_at &&
				$subscription->ends_at->isFuture()
					? $subscription->ends_at->copy()
					: now();

			$newEndDate = match ($cycle) {
				'daily' => $baseDate->addDay(),
				'weekly' => $baseDate->addWeek(),
				'monthly' => $baseDate->addMonth(),
				'quarterly' => $baseDate->addMonths(3),
				'semiannual' => $baseDate->addMonths(6),
				'yearly', 'annual' => $baseDate->addYear(),
				default => $baseDate->addMonth(),
			};

			$subscription->update([
				'status' => 'active',
				'starts_at' => $subscription->status === 'expired'
					? now()
					: $subscription->starts_at,
				'ends_at' => $newEndDate,
			]);
		} else {
			$subscription->update([
				'status' => 'active',
			]);
		}

        return $payment->fresh(['subscription']);
    }

    public function getProvider(
        string $paymentMethod
    ): PaymentProviderInterface {
        return match ($paymentMethod) {
            'wave' => app(WavePaymentProvider::class),

            default => throw new \InvalidArgumentException(
                "Méthode de paiement non supportée : {$paymentMethod}"
            ),
        };
    }

    public function initiatePayment(
    Payment $payment,
    array $data = []
	): array {
		if ($payment->status === 'paid') {
			return [
				'success' => true,
				'status' => 'already_paid',
				'message' => 'Ce paiement est déjà confirmé.',
				'payment_id' => $payment->id,
				'reference' => $payment->reference,
			];
		}

		$provider = $this->getProvider(
			$payment->payment_method
		);

		return $provider->initiate(
			$payment,
			$data
		);
	}

    public function findByProviderTransactionId(
        string $transactionId
    ): ?Payment {
        return Payment::where(
            'provider_transaction_id',
            $transactionId
        )->first();
    }
	
	public function updatePaymentStatus(
		Payment $payment,
		string $status,
		?array $metadata = null
	): Payment {
		if ($payment->status === 'paid') {
			return $payment->fresh(['subscription']);
		}

		$payment->update([
			'status' => $status,
			'metadata' => array_merge(
				$payment->metadata ?? [],
				$metadata ?? []
			),
		]);

		$subscription = $payment->subscription;

		if ($status === 'failed') {
			$subscription->update([
				'status' => 'past_due',
			]);
		}

		return $payment->fresh(['subscription']);
	}
}
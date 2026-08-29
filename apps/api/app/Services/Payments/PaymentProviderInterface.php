<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentProviderInterface
{
    public function initiate(Payment $payment, array $data = []): array;

    public function verify(Payment $payment, array $data = []): array;
}
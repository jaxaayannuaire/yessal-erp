<?php

namespace App\Enums\Caisse;

enum SaleStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Credit = 'credit';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}

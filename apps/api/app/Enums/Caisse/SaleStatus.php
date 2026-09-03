<?php

namespace App\Enums\Caisse;

enum SaleStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Finalized = 'finalized';
    case PartiallyPaid = 'partially_paid';
    case Credit = 'credit';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}

<?php

namespace App\Enums\Caisse;

enum CashSessionStatus: string
{
    case Open = 'open';
    case Closing = 'closing';
    case Closed = 'closed';
}

<?php

namespace App\Enums\Caisse;

enum SyncEventStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Acked = 'acked';
    case Failed = 'failed';
    case Rejected = 'rejected';
    case Quarantined = 'quarantined';
}

<?php

namespace App\Policies\Caisse;

use App\Models\User;
use App\Models\Caisse\Shop;

class CaissePolicy
{
    public function accessShop(User $user, Shop $shop): bool
    {
        return $user->organizations()
            ->whereKey($shop->organization_id)
            ->exists();
    }
}

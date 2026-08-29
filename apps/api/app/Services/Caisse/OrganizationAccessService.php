<?php

namespace App\Services\Caisse;

use App\Models\User;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Models\Caisse\Device;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrganizationAccessService
{
    public function ensureShop(User $user, Shop $shop): void
    {
        if (! $user->organizations()->whereKey($shop->organization_id)->exists()) {
            throw new AccessDeniedHttpException('Organisation inaccessible.');
        }
    }

    public function ensureTerminal(Shop $shop, Terminal $terminal): void
    {
        if ((int) $terminal->shop_id !== (int) $shop->id) {
            throw new AccessDeniedHttpException('Terminal inaccessible.');
        }
    }

    public function ensureDevice(Device $device, int $organizationId, ?int $shopId = null): void
    {
        if ((int) $device->organization_id !== $organizationId ||
            ($shopId !== null && (int) $device->shop_id !== $shopId)) {
            throw new AccessDeniedHttpException('Appareil inaccessible.');
        }
    }
}

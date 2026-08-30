<?php

namespace App\Services\Caisse;

use App\Models\Caisse\Device;
use App\Models\Organization;
use App\Services\Entitlements\QuotaService;
use Illuminate\Support\Facades\DB;

class DeviceQuotaService
{
    public function __construct(
        private readonly QuotaService $quota
    ) {
    }

    /**
     * Exécute une opération nécessitant une place active
     * sous verrou transactionnel de l'organisation.
     *
     * @param callable(Organization): Device $callback
     */
    public function withActiveDeviceQuota(
        Organization $organization,
        callable $callback
    ): Device {
        return DB::transaction(function () use ($organization, $callback) {
            $lockedOrganization = Organization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->quota->canAdd(
                $lockedOrganization,
                'devices'
            )) {
                throw new DeviceQuotaExceededException(
                    $this->quota->check(
                        $lockedOrganization,
                        'devices'
                    )
                );
            }

            return $callback($lockedOrganization);
        });
    }

    /**
     * Crée un appareil actif avec contrôle atomique du quota.
     */
    public function createActiveDevice(
        Organization $organization,
        array $attributes
    ): Device {
        return $this->withActiveDeviceQuota(
            $organization,
            function (Organization $lockedOrganization) use ($attributes): Device {
                return Device::create([
                    ...$attributes,
                    'organization_id' => $lockedOrganization->id,
                    'status' => 'active',
                ]);
            }
        );
    }

    /**
     * Réactive un appareil avec contrôle atomique du quota.
     */
    public function activateDevice(
        Organization $organization,
        Device $device
    ): Device {
		if ((int) $device->organization_id !== (int) $organization->id) {
			throw new \InvalidArgumentException(
				'L’appareil n’appartient pas à l’organisation.'
			);
		}
        return $this->withActiveDeviceQuota(
            $organization,
            function (Organization $lockedOrganization) use ($device): Device {
                $device->update([
                    'status' => 'active',
                    'revoked_at' => null,
                ]);

                return $device->fresh();
            }
        );
    }
}
<?php

namespace App\Services\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Customer;
use App\Models\Caisse\Device;
use App\Models\Caisse\DeviceActivityLog;
use App\Models\Caisse\Shop;
use App\Models\Caisse\SyncEvent;
use App\Models\Caisse\Terminal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncService
{
    private const SUPPORTED_EVENT_TYPES = ['sale' => ['create', 'created']];

    public function __construct(
        private readonly ?SaleService $sales = null,
        private readonly ?PaymentService $payments = null,
        private readonly ?SyncChangeService $changes = null,
    ) {
    }

    public function push(int $organizationId, int $deviceId, array $events, int|array $userId = 0, array $activity = []): array
    {
        if (is_array($userId)) {
            $activity = $userId;
            $userId = 0;
        }
        $device = Device::query()->whereKey($deviceId)->where('organization_id', $organizationId)->where('status', 'active')->first();
        if (! $device) {
            throw ValidationException::withMessages(['device_id' => ['Appareil inexistant, inactif ou inaccessible.']]);
        }

        $now = now();
        $device->update(['last_seen_at' => $now]);
        $this->logActivity($organizationId, $device, 'connected', $activity);
        $accepted = [];
        $rejected = [];
        $conflicts = [];
        $failed = [];

        foreach ($events as $event) {
            try {
                $result = $this->processEvent($organizationId, $device, $userId, $event);
                match ($result['type']) {
                    'conflict' => $conflicts[] = $result,
                    'rejected' => $rejected[] = $result,
                    'failed' => $failed[] = $result,
                    default => $accepted[] = $result,
                };
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }
                $existing = $this->findSyncEvent($organizationId, $event['event_uuid']);
                if (! $existing) {
                    throw $exception;
                }
                $accepted[] = $this->duplicateResult($existing);
            }
        }

        $device->update(['last_seen_at' => $now, 'last_sync_at' => $now]);
        $this->logActivity($organizationId, $device, 'sync_push', array_merge($activity, ['events_count' => count($events), 'accepted_count' => count($accepted), 'rejected_count' => count($rejected)]));
        foreach ($rejected as $item) {
            $this->logActivity($organizationId, $device, 'sync_rejected', ['event_uuid' => $item['event_uuid'] ?? null, 'errors' => $item['errors'] ?? []] + $activity);
        }

        return compact('accepted', 'rejected', 'conflicts', 'failed');
    }

    private function processEvent(int $organizationId, Device $device, int $userId, array $event): array
    {
        $existing = $this->findSyncEvent($organizationId, $event['event_uuid']);
        if ($existing) {
            return $this->duplicateResult($existing);
        }

        if (! $this->isSupportedEvent($event)) {
            return ['type' => 'rejected', 'status' => 'rejected', 'event_uuid' => $event['event_uuid'], 'errors' => ['entity_type' => ['Type ou action de synchronisation non pris en charge.']]];
        }

        if (! isset($event['payload']['local_uuid'])) {
            try {
                return $this->recordLegacyEvent($organizationId, $device, $event);
            } catch (ValidationException $exception) {
                return ['type' => 'rejected', 'status' => 'rejected', 'event_uuid' => $event['event_uuid'], 'errors' => $exception->errors()];
            }
        }

        try {
            $this->validateEventShop($organizationId, $device, $event);
        } catch (ValidationException $exception) {
            return ['type' => 'rejected', 'status' => 'rejected', 'event_uuid' => $event['event_uuid'], 'errors' => $exception->errors()];
        }

        $syncEvent = SyncEvent::create([
            'organization_id' => $organizationId, 'shop_id' => $event['shop_id'], 'device_id' => $device->id,
            'event_uuid' => $event['event_uuid'], 'entity_type' => $event['entity_type'], 'entity_id' => $event['entity_id'],
            'action' => $event['action'], 'payload' => $event['payload'], 'status' => 'pending',
            'created_at' => $event['occurred_at'] ?? now(),
        ]);

        try {
            return DB::transaction(function () use ($syncEvent, $organizationId, $device, $userId) {
                $syncEvent = SyncEvent::query()->lockForUpdate()->findOrFail($syncEvent->id);
                $sale = $this->applySaleCreate($organizationId, $device, $userId, $syncEvent);
                $result = ['sale_id' => $sale->id, 'status' => $sale->status];
                ($this->changes ?? app(SyncChangeService::class))->record($organizationId, 'sale', $sale, $device->id);
                $syncEvent->update(['status' => 'applied', 'result' => $result, 'error_code' => null, 'processed_at' => now()]);
                return ['type' => 'accepted', 'status' => 'applied', 'id' => $syncEvent->id, 'duplicate' => false, 'result' => $result];
            });
        } catch (ValidationException $exception) {
            $status = $this->isStockConflict($exception) ? 'conflict' : 'rejected';
            $syncEvent->update(['status' => $status, 'error_code' => $status === 'conflict' ? 'stock_insufficient' : 'validation_failed', 'processed_at' => now()]);
            return ['type' => $status, 'status' => $status, 'id' => $syncEvent->id, 'event_uuid' => $syncEvent->event_uuid, 'errors' => $exception->errors()];
        } catch (Throwable $exception) {
            report($exception);
            $syncEvent->update(['status' => 'failed', 'error_code' => 'processing_failed', 'processed_at' => now()]);
            return ['type' => 'failed', 'status' => 'failed', 'id' => $syncEvent->id, 'event_uuid' => $syncEvent->event_uuid];
        }
    }

    private function applySaleCreate(int $organizationId, Device $device, int $userId, SyncEvent $event): \App\Models\Caisse\Sale
    {
        $data = Validator::make($event->payload, [
            'terminal_id' => ['required', 'integer'], 'cash_session_id' => ['required', 'integer'],
            'local_uuid' => ['required', 'uuid'], 'receipt_number' => ['required', 'string', 'max:100'], 'currency' => ['required', 'string', 'size:3'],
            'customer_id' => ['nullable', 'integer'], 'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer'], 'lines.*.product_variant_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'], 'lines.*.unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'integer', 'min:0'], 'lines.*.tax_amount' => ['nullable', 'integer', 'min:0'],
            'payment' => ['nullable', 'array'], 'payment.method' => ['required_with:payment', 'string'],
            'payment.amount' => ['required_with:payment', 'integer', 'gt:0'], 'payment.reference' => ['nullable', 'string', 'max:150'],
            'finalize' => ['nullable', 'boolean'],
        ])->validate();
        $shop = Shop::query()->whereKey($event->shop_id)->where('organization_id', $organizationId)->firstOrFail();
        $terminal = Terminal::query()->whereKey($data['terminal_id'])->where('shop_id', $shop->id)->first();
        $session = CashSession::query()->whereKey($data['cash_session_id'])->where('organization_id', $organizationId)->where('shop_id', $shop->id)->where('terminal_id', $data['terminal_id'])->where('status', 'open')->first();
        if (! $terminal || ! $session) {
            throw ValidationException::withMessages(['cash_session_id' => ['La session de caisse doit être ouverte et correspondre à la boutique et au terminal.']]);
        }
        if (isset($data['customer_id']) && ! Customer::query()->whereKey($data['customer_id'])->where('status', 'active')->whereHas('shop', fn ($query) => $query->where('organization_id', $organizationId))->exists()) {
            throw ValidationException::withMessages(['customer_id' => ['Client introuvable, inactif ou inaccessible pour cette organisation.']]);
        }
        $sale = ($this->sales ?? app(SaleService::class))->create($data + ['organization_id' => $organizationId, 'shop_id' => $shop->id, 'device_id' => $device->id, 'cashier_user_id' => $userId]);
        if (isset($data['payment'])) {
            if ($data['payment']['method'] !== 'cash') {
                throw ValidationException::withMessages(['payment.method' => ['Seul le paiement cash est pris en charge hors ligne.']]);
            }
            ($this->payments ?? app(PaymentService::class))->payCash($sale, (int) $data['payment']['amount'], $data['payment']['reference'] ?? null);
        }
        if (($data['finalize'] ?? false) === true) {
            $sale = ($this->sales ?? app(SaleService::class))->finalize($sale);
        }
        return $sale->fresh()->load(['lines', 'payments']);
    }

    private function validateEventShop(int $organizationId, Device $device, array $event): void
    {
        $shopId = $event['shop_id'] ?? null;
        $shop = $shopId === null ? null : Shop::query()->whereKey($shopId)->where('organization_id', $organizationId)->first();
        if (! $shop || ($device->shop_id !== null && (int) $device->shop_id !== (int) $shopId)) {
            throw ValidationException::withMessages(['shop_id' => ['Boutique inexistante ou inaccessible.']]);
        }
    }

    private function recordLegacyEvent(int $organizationId, Device $device, array $event): array
    {
        $shopId = $event['shop_id'] ?? null;
        if ($shopId !== null) {
            $this->validateEventShop($organizationId, $device, $event);
        }
        $created = DB::transaction(fn () => $this->createSyncEvent([
            'organization_id' => $organizationId, 'shop_id' => $shopId, 'device_id' => $device->id,
            'event_uuid' => $event['event_uuid'], 'entity_type' => $event['entity_type'], 'entity_id' => $event['entity_id'],
            'action' => $event['action'], 'payload' => $event['payload'], 'status' => 'pending', 'created_at' => $event['occurred_at'] ?? now(),
        ]));
        return ['type' => 'accepted', 'status' => 'pending', 'id' => $created->id, 'duplicate' => false];
    }

    private function duplicateResult(SyncEvent $event): array { return ['type' => 'accepted', 'status' => $event->status === 'applied' ? 'applied' : 'duplicate', 'id' => $event->id, 'duplicate' => true, 'result' => $event->result]; }
    private function isSupportedEvent(array $event): bool { return in_array($event['action'], self::SUPPORTED_EVENT_TYPES[$event['entity_type']] ?? [], true); }
    protected function createSyncEvent(array $attributes): SyncEvent { return SyncEvent::create($attributes); }
    protected function findSyncEvent(int $organizationId, string $eventUuid): ?SyncEvent { return SyncEvent::query()->where('organization_id', $organizationId)->where('event_uuid', $eventUuid)->first(); }
    private function isUniqueConstraintViolation(QueryException $exception): bool { return in_array((string) $exception->getCode(), ['19', '23000', '23505'], true); }
    private function isStockConflict(ValidationException $exception): bool { return str_contains(strtolower(json_encode($exception->errors())), 'stock disponible est insuffisant'); }
    private function logActivity(int $organizationId, Device $device, string $eventType, array $activity = []): void { DeviceActivityLog::create(['organization_id' => $organizationId, 'device_id' => $device->id, 'event_type' => $eventType, 'ip_address' => $activity['ip_address'] ?? null, 'user_agent' => $activity['user_agent'] ?? null, 'app_version' => $activity['app_version'] ?? $device->app_version, 'metadata' => $activity['metadata'] ?? null, 'created_at' => now()]); }
}

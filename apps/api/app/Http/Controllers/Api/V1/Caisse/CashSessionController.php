<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Device;
use App\Models\Caisse\Terminal;
use App\Services\Caisse\CashSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    public function __construct(
        private CashSessionService $cashSessions
    ) {}

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $sessions = CashSession::query()
            ->where('organization_id', $organization->id)
            ->with(['shop', 'terminal', 'device', 'opener', 'closer'])
            ->latest('opened_at')
            ->get();

        return response()->json([
            'success' => true,
            'cash_sessions' => $sessions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');

        $validated = $request->validate([
            'terminal_id' => ['required', 'integer', 'exists:terminals,id'],
            'device_id' => ['nullable', 'integer', 'exists:devices,id'],
            'opening_amount' => ['required', 'integer', 'min:0'],
        ]);

        $terminal = Terminal::query()
            ->whereKey($validated['terminal_id'])
            ->whereHas('shop', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->first();

        if (! $terminal) {
            abort(403, 'Terminal inaccessible.');
        }

        if (! empty($validated['device_id'])) {
            $device = Device::query()
                ->whereKey($validated['device_id'])
                ->where('organization_id', $organization->id)
                ->first();

            if (! $device) {
                abort(403, 'Appareil inaccessible.');
            }

            if (
                $device->shop_id !== null &&
                (int) $device->shop_id !== (int) $terminal->shop_id
            ) {
                abort(403, 'Appareil inaccessible pour cette boutique.');
            }

            if (
                $device->terminal_id !== null &&
                (int) $device->terminal_id !== (int) $terminal->id
            ) {
                abort(403, 'Appareil inaccessible pour ce terminal.');
            }
        }

        $session = $this->cashSessions->open($terminal, [
            'organization_id' => $organization->id,
            'device_id' => $validated['device_id'] ?? null,
            'opened_by' => $request->user()->id,
            'opening_amount' => $validated['opening_amount'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session de caisse ouverte avec succès.',
            'cash_session' => $session->load([
                'shop',
                'terminal',
                'device',
                'opener',
            ]),
        ], 201);
    }

    public function show(
        Request $request,
        CashSession $cashSession
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureOrganizationAccess($cashSession, $organization->id);

        return response()->json([
            'success' => true,
            'cash_session' => $cashSession->load([
                'shop',
                'terminal',
                'device',
                'opener',
                'closer',
                'movements',
            ]),
        ]);
    }

    public function close(
        Request $request,
        CashSession $cashSession
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureOrganizationAccess($cashSession, $organization->id);

        $validated = $request->validate([
            'counted_amount' => ['required', 'integer', 'min:0'],
            'variance_reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $session = $this->cashSessions->close(
            $cashSession,
            $validated['counted_amount'],
            $validated['variance_reason'] ?? null
        );

        $session->update([
            'closed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session de caisse fermée avec succès.',
            'cash_session' => $session->fresh()->load([
                'shop',
                'terminal',
                'device',
                'opener',
                'closer',
            ]),
        ]);
    }

    private function ensureOrganizationAccess(
        CashSession $session,
        int $organizationId
    ): void {
        if ((int) $session->organization_id !== $organizationId) {
            abort(403, 'Session de caisse inaccessible.');
        }
    }
}
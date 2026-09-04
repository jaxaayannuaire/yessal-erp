<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\CashMovement;
use App\Models\Caisse\CashSession;
use App\Services\Caisse\CashMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashMovementController extends Controller
{
    public function __construct(
        private CashMovementService $cashMovements
    ) {}

    public function index(
        Request $request,
        CashSession $cashSession
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureOrganizationAccess($cashSession, $organization->id);

        return response()->json([
            'success' => true,
            'movements' => CashMovement::query()
                ->where('organization_id', $organization->id)
                ->where('cash_session_id', $cashSession->id)
                ->with('creator:id,name')
                ->latest('created_at')
                ->paginate(min($request->integer('per_page', 50), 100)),
        ]);
    }

    public function store(
        Request $request,
        CashSession $cashSession
    ): JsonResponse {
        $organization = $request->attributes->get('currentOrganization');

        $this->ensureOrganizationAccess($cashSession, $organization->id);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['cash_in', 'cash_out'])],
            'amount' => ['required', 'integer', 'gt:0'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        $movement = $this->cashMovements->createManualMovement(
            $cashSession,
            $request->user()->id,
            $validated
        );

        return response()->json([
            'success' => true,
            'movement' => $movement->load('creator:id,name'),
        ], 201);
    }

    private function ensureOrganizationAccess(
        CashSession $cashSession,
        int $organizationId
    ): void {
        if ((int) $cashSession->organization_id !== $organizationId) {
            abort(403, 'Session de caisse inaccessible.');
        }
    }
}

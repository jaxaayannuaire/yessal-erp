<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SaleReturn;
use App\Services\Caisse\SaleRefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleRefundController extends Controller
{
    public function __construct(
        private readonly SaleRefundService $refunds
    ) {
    }

    public function index(Request $request, Sale $sale): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        $this->ensureOrganizationAccess($sale, $organizationId);

        return response()->json([
            'success' => true,
            'refunds' => SaleReturn::query()
                ->where('organization_id', $organizationId)
                ->where('sale_id', $sale->id)
                ->with(['payment', 'creator'])
                ->latest('id')
                ->paginate(min($request->integer('per_page', 25), 100)),
        ]);
    }

    public function store(Request $request, Sale $sale): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        $this->ensureOrganizationAccess($sale, $organizationId);

        $validated = $request->validate([
            'sale_payment_id' => ['required', 'integer', 'exists:sale_payments,id'],
            'amount' => ['required', 'integer', 'gt:0'],
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        $refund = $this->refunds->refund(
            $sale,
            (int) $validated['sale_payment_id'],
            (int) $validated['amount'],
            $validated['reason'],
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'refund' => $refund,
            'sale' => $sale->fresh()->load(['payments', 'returns']),
        ], 201);
    }

    private function ensureOrganizationAccess(Sale $sale, int $organizationId): void
    {
        if ((int) $sale->organization_id !== $organizationId) {
            abort(403, 'Vente inaccessible.');
        }
    }
}

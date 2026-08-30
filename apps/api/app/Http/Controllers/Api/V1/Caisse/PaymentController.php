<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SalePayment;
use App\Services\Caisse\OrganizationAccessService;
use App\Services\Caisse\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly OrganizationAccessService $accessService,
    ) {
    }

    public function index(Request $request, Sale $sale): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        if ((int) $sale->organization_id !== $organizationId) {
            abort(403, 'Vente inaccessible.');
        }

        return response()->json([
            'success' => true,
            'payments' => $sale->payments()
                ->latest('id')
                ->get(),
        ]);
    }

    public function payCash(
        Request $request,
        Sale $sale
    ): JsonResponse {
        $organizationId = (int) $request->attributes->get('organization_id');

        if ((int) $sale->organization_id !== $organizationId) {
            abort(403, 'Vente inaccessible.');
        }

        $validated = $request->validate([
            'amount' => [
                'required',
                'integer',
                'gt:0',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $payment = $this->paymentService->payCash(
            $sale,
            (int) $validated['amount'],
            $validated['reference'] ?? null
        );

        return response()->json([
            'success' => true,
            'payment' => $payment,
            'sale' => $sale->fresh()->load([
                'lines',
                'payments',
            ]),
        ], 201);
    }
}
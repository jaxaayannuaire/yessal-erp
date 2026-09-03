<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\CashSession;
use App\Models\Caisse\Sale;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use App\Services\Caisse\OrganizationAccessService;
use App\Services\Caisse\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly OrganizationAccessService $accessService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        $sales = Sale::query()
            ->with(['lines', 'payments'])
            ->where('organization_id', $organizationId)
            ->when(
                $request->filled('shop_id'),
                fn ($query) => $query->where(
                    'shop_id',
                    $request->integer('shop_id')
                )
            )
            ->when(
                $request->filled('terminal_id'),
                fn ($query) => $query->where(
                    'terminal_id',
                    $request->integer('terminal_id')
                )
            )
            ->when(
                $request->filled('cash_session_id'),
                fn ($query) => $query->where(
                    'cash_session_id',
                    $request->integer('cash_session_id')
                )
            )
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->string('status')->toString()
                )
            )
            ->latest('id')
            ->paginate(
                min(
                    $request->integer('per_page', 25),
                    100
                )
            );

        return response()->json([
            'success' => true,
            'sales' => $sales,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'terminal_id' => ['required', 'integer', 'exists:terminals,id'],
            'cash_session_id' => [
                'required',
                'integer',
                'exists:cash_sessions,id',
            ],
            'device_id' => [
                'nullable',
                'integer',
                'exists:devices,id',
            ],
            'seller_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],
            'local_uuid' => ['required', 'uuid'],
            'receipt_number' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'nullable',
                'integer',
                'exists:products,id',
            ],
            'lines.*.product_variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
            ],
            'lines.*.product_name_snapshot' => [
                'nullable',
                'string',
                'max:255',
            ],
            'lines.*.sku_snapshot' => [
                'nullable',
                'string',
                'max:100',
            ],
            'lines.*.barcode_snapshot' => [
                'nullable',
                'string',
                'max:100',
            ],
            'lines.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'lines.*.unit_price' => [
                'required',
                'integer',
                'min:0',
            ],
            'lines.*.discount_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'lines.*.tax_amount' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $shop = Shop::query()->findOrFail($validated['shop_id']);
        $terminal = Terminal::query()->findOrFail($validated['terminal_id']);
        $session = CashSession::query()->findOrFail(
            $validated['cash_session_id']
        );

        $this->accessService->ensureShop(
            $request->user(),
            $shop
        );

        $this->accessService->ensureTerminal(
            $shop,
            $terminal
        );

        if ((int) $session->organization_id !== $organizationId ||
            (int) $session->shop_id !== (int) $shop->id ||
            (int) $session->terminal_id !== (int) $terminal->id) {
            throw ValidationException::withMessages([
                'cash_session_id' => [
                    'La session de caisse ne correspond pas à cette boutique et ce terminal.',
                ],
            ]);
        }

        if ($session->status !== 'open') {
            throw ValidationException::withMessages([
                'cash_session_id' => [
                    'La session de caisse doit être ouverte.',
                ],
            ]);
        }

        $data = $validated;
        $data['organization_id'] = $organizationId;
        $data['cashier_user_id'] = $request->user()->id;

        $sale = $this->saleService->create($data);

        return response()->json([
            'success' => true,
            'sale' => $sale->load(['lines', 'payments']),
        ], 201);
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        if ((int) $sale->organization_id !== $organizationId) {
            abort(403, 'Vente inaccessible.');
        }

        return response()->json([
            'success' => true,
            'sale' => $sale->load([
                'lines',
                'payments',
                'shop',
                'terminal',
                'cashSession',
                'cashier',
                'seller',
                'customer',
            ]),
        ]);
    }

    public function finalize(Request $request, Sale $sale): JsonResponse
    {
        $organizationId = (int) $request->attributes->get('organization_id');

        if ((int) $sale->organization_id !== $organizationId) {
            abort(403, 'Vente inaccessible.');
        }

        $sale = $this->saleService->finalize($sale);

        return response()->json([
            'success' => true,
            'sale' => $sale->fresh()->load([
                'lines',
                'payments',
            ]),
        ]);
    }
}

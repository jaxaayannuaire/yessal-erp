<?php

namespace App\Http\Controllers\Api\V1\Caisse;

use App\Http\Controllers\Controller;
use App\Models\Caisse\Shop;
use App\Services\Caisse\ReportingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function __construct(private readonly ReportingService $reporting)
    {
    }

    public function overview(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('currentOrganization');
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'shop_id' => ['nullable', 'integer'],
        ]);

        $from = isset($validated['from'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['from'])->startOfDay()
            : now()->toImmutable()->startOfDay();
        $to = isset($validated['to'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['to'])->endOfDay()
            : now()->toImmutable()->endOfDay();
        $shopId = $validated['shop_id'] ?? null;

        if ($shopId !== null && ! Shop::query()
            ->whereKey($shopId)
            ->where('organization_id', $organization->id)
            ->exists()) {
            abort(403, 'Boutique inaccessible.');
        }

        return response()->json([
            'success' => true,
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            ...$this->reporting->overview($organization, $from, $to, $shopId),
        ]);
    }
}

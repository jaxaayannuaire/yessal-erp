<?php

namespace App\Services\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Customer;
use App\Models\Caisse\Sale;
use App\Models\Caisse\SalePayment;
use App\Models\Caisse\Shop;
use App\Models\Caisse\StockLevel;
use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ReportingService
{
    public function overview(
        Organization $organization,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $shopId = null
    ): array {
        $sales = $this->finalizedSalesQuery($organization, $from, $to, $shopId);
        $salesCount = (int) (clone $sales)->count();
        $grossAmount = (int) ((clone $sales)->sum('total_amount') ?? 0);
        $paidAmount = $this->confirmedPaymentsQuery(
            $organization,
            $from,
            $to,
            $shopId
        )->sum('sale_payments.amount');

        return [
            'sales' => [
                'count' => $salesCount,
                'gross_amount' => $grossAmount,
                'paid_amount' => (int) $paidAmount,
                'cancelled_count' => $this->cancelledSalesQuery(
                    $organization,
                    $from,
                    $to,
                    $shopId
                )->count(),
                'average_ticket' => $salesCount === 0
                    ? 0
                    : (int) round($grossAmount / $salesCount),
            ],
            'payments' => $this->paymentBreakdown(
                $organization,
                $from,
                $to,
                $shopId
            ),
            'cash_sessions' => $this->cashSessionMetrics(
                $organization,
                $from,
                $to,
                $shopId
            ),
            'stock' => $this->stockMetrics($organization, $shopId),
            'shops' => $this->shopMetrics($organization, $from, $to, $shopId),
            'customers' => [
                'active_count' => $this->activeCustomerCount($organization, $shopId),
            ],
        ];
    }

    private function finalizedSalesQuery(
        Organization $organization,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $shopId
    ): Builder {
        return Sale::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'finalized')
            ->whereBetween('finalized_at', [$from, $to])
            ->when($shopId, fn ($query) => $query->where('shop_id', $shopId));
    }

    private function cancelledSalesQuery(
        Organization $organization,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $shopId
    ): Builder {
        return Sale::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'cancelled')
            ->whereBetween('updated_at', [$from, $to])
            ->when($shopId, fn ($query) => $query->where('shop_id', $shopId));
    }

    private function confirmedPaymentsQuery(
        Organization $organization,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $shopId
    ) {
        return SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->where('sales.organization_id', $organization->id)
            ->where('sales.status', 'finalized')
            ->whereBetween('sales.finalized_at', [$from, $to])
            ->where('sale_payments.status', 'confirmed')
            ->when($shopId, fn ($query) => $query->where('sales.shop_id', $shopId));
    }

    private function paymentBreakdown(
        Organization $organization,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $shopId
    ): array {
        return $this->confirmedPaymentsQuery($organization, $from, $to, $shopId)
            ->selectRaw(
                'sale_payments.payment_method, COUNT(*) as count, COALESCE(SUM(sale_payments.amount), 0) as amount'
            )
            ->groupBy('sale_payments.payment_method')
            ->get()
            ->mapWithKeys(fn ($payment) => [
                $payment->payment_method => [
                    'count' => (int) $payment->count,
                    'amount' => (int) $payment->amount,
                ],
            ])
            ->all();
    }

    private function cashSessionMetrics(
        Organization $organization,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $shopId
    ): array {
        $sessions = CashSession::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('opened_at', [$from, $to])
            ->when($shopId, fn ($query) => $query->where('shop_id', $shopId));

        return [
            'opened_count' => (int) (clone $sessions)
                ->where('status', 'open')
                ->count(),
            'closed_count' => (int) (clone $sessions)
                ->where('status', 'closed')
                ->count(),
            'opening_amount_total' => (int) ((clone $sessions)
                ->sum('opening_amount') ?? 0),
            'closing_amount_total' => (int) ((clone $sessions)
                ->where('status', 'closed')
                ->sum('counted_amount') ?? 0),
        ];
    }

    private function stockMetrics(Organization $organization, ?int $shopId): array
    {
        $levels = StockLevel::query()
            ->whereHas('location', fn ($query) => $query
                ->where('organization_id', $organization->id)
                ->when($shopId, fn ($query) => $query->where('shop_id', $shopId)));

        $productsWithStock = (int) (clone $levels)
            ->where('quantity', '>', 0)
            ->whereNotNull('product_id')
            ->distinct()
            ->count('product_id');
        $variantsWithStock = (int) (clone $levels)
            ->where('quantity', '>', 0)
            ->whereNotNull('product_variant_id')
            ->distinct()
            ->count('product_variant_id');

        return [
            'products_with_stock' => $productsWithStock + $variantsWithStock,
            'total_units' => (float) ((clone $levels)->sum('quantity') ?? 0),
        ];
    }

    private function shopMetrics(
        Organization $organization,
        CarbonImmutable $from,
        CarbonImmutable $to,
        ?int $shopId
    ): array {
        $shops = Shop::query()
            ->where('organization_id', $organization->id)
            ->when($shopId, fn ($query) => $query->whereKey($shopId))
            ->orderBy('name')
            ->get(['id', 'name']);
        $sales = $this->finalizedSalesQuery($organization, $from, $to, $shopId)
            ->selectRaw(
                'shop_id, COUNT(*) as sales_count, COALESCE(SUM(total_amount), 0) as gross_amount'
            )
            ->groupBy('shop_id')
            ->get()
            ->keyBy('shop_id');
        $payments = $this->confirmedPaymentsQuery(
            $organization,
            $from,
            $to,
            $shopId
        )
            ->selectRaw('sales.shop_id, COALESCE(SUM(sale_payments.amount), 0) as paid_amount')
            ->groupBy('sales.shop_id')
            ->get()
            ->keyBy('shop_id');

        return $shops->map(function (Shop $shop) use ($sales, $payments) {
            $sale = $sales->get($shop->id);
            $payment = $payments->get($shop->id);

            return [
                'id' => $shop->id,
                'name' => $shop->name,
                'sales_count' => (int) ($sale->sales_count ?? 0),
                'gross_amount' => (int) ($sale->gross_amount ?? 0),
                'paid_amount' => (int) ($payment->paid_amount ?? 0),
            ];
        })->all();
    }

    private function activeCustomerCount(Organization $organization, ?int $shopId): int
    {
        return Customer::query()
            ->where('status', 'active')
            ->whereHas('shop', fn ($query) => $query
                ->where('organization_id', $organization->id)
                ->when($shopId, fn ($query) => $query->whereKey($shopId)))
            ->count();
    }
}

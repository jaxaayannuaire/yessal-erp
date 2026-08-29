<?php

namespace Database\Factories\Caisse;

use App\Models\Caisse\CashSession;
use App\Models\Caisse\Sale;
use App\Models\Caisse\Shop;
use App\Models\Caisse\Terminal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $terminal = Terminal::factory()->create();
        $shop = Shop::find($terminal->shop_id);
        $session = CashSession::factory()->create([
            'organization_id' => $shop->organization_id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
        ]);

        return [
            'organization_id' => $shop->organization_id,
            'shop_id' => $shop->id,
            'terminal_id' => $terminal->id,
            'cash_session_id' => $session->id,
            'device_id' => null,
            'cashier_user_id' => null,
            'seller_user_id' => null,
            'customer_id' => null,
            'local_uuid' => Str::uuid(),
            'receipt_number' => strtoupper(fake()->unique()->bothify('TST-#####')),
            'status' => 'draft',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'paid_amount' => 0,
            'due_amount' => 0,
            'currency' => 'XOF',
            'finalized_at' => null,
        ];
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 200);
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('unit', 30)->default('unit');
            $table->bigInteger('purchase_price')->nullable();
            $table->bigInteger('sale_price');
            $table->decimal('tax_rate', 8, 4)->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'barcode']);
        });

        DB::statement("CREATE UNIQUE INDEX products_shop_sku_unique ON products (shop_id, sku) WHERE sku IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX products_shop_barcode_unique ON products (shop_id, barcode) WHERE barcode IS NOT NULL");
    }

    public function down(): void { Schema::dropIfExists('products'); }
};

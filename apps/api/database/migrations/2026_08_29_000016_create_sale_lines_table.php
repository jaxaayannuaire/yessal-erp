<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('product_name_snapshot', 200);
            $table->string('sku_snapshot', 100)->nullable();
            $table->string('barcode_snapshot', 100)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->bigInteger('unit_price');
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_amount');
            $table->timestamps();

            $table->index('sale_id');
        });
    }

    public function down(): void { Schema::dropIfExists('sale_lines'); }
};

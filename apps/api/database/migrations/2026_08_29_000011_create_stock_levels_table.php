<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE stock_levels ADD CONSTRAINT stock_levels_product_xor_variant CHECK ((product_id IS NOT NULL) <> (product_variant_id IS NOT NULL))");
        DB::statement("CREATE UNIQUE INDEX stock_levels_location_product_unique ON stock_levels (stock_location_id, product_id) WHERE product_id IS NOT NULL");
        DB::statement("CREATE UNIQUE INDEX stock_levels_location_variant_unique ON stock_levels (stock_location_id, product_variant_id) WHERE product_variant_id IS NOT NULL");
    }

    public function down(): void { Schema::dropIfExists('stock_levels'); }
};

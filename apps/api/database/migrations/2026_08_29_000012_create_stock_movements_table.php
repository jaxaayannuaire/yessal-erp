<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('type', 40);
            $table->decimal('quantity', 12, 3);
            $table->bigInteger('unit_cost')->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['organization_id', 'stock_location_id', 'created_at']);
            $table->index(['stock_location_id', 'product_id', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('stock_movements'); }
};

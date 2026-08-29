<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('type', 30)->default('store');
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('stock_locations'); }
};

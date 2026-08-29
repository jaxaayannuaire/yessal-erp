<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->boolean('credit_enabled')->default(false);
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->index(['shop_id', 'phone']);
        });
    }

    public function down(): void { Schema::dropIfExists('customers'); }
};

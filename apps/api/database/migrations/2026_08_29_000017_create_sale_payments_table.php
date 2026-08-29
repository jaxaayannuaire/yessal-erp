<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('payment_method', 40);
            $table->string('provider', 50)->nullable();
            $table->bigInteger('amount');
            $table->bigInteger('change_amount')->default(0);
            $table->string('status', 30)->default('pending');
            $table->string('external_reference', 150)->nullable();
            $table->timestampTz('declared_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('sale_payments'); }
};

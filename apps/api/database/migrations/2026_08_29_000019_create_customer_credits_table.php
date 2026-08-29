<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->bigInteger('original_amount');
            $table->bigInteger('paid_amount')->default(0);
            $table->bigInteger('remaining_amount');
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('customer_credits'); }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('reference_number', 100);
            $table->text('reason')->nullable();
            $table->bigInteger('amount');
            $table->string('refund_method', 40)->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'reference_number']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('sale_returns'); }
};

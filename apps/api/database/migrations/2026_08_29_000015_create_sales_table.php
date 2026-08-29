<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained('terminals')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('cashier_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->uuid('local_uuid');
            $table->string('receipt_number', 100);
            $table->string('status', 40)->default('draft');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->bigInteger('due_amount')->default(0);
            $table->char('currency', 3);
            $table->timestampTz('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'local_uuid']);
            $table->unique(['organization_id', 'receipt_number']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['shop_id', 'created_at']);
            $table->index('cash_session_id');
        });

        DB::statement("
            ALTER TABLE sales
            ADD CONSTRAINT sales_total_amount_non_negative
            CHECK (total_amount >= 0)
        ");

        DB::statement("
            ALTER TABLE sales
            ADD CONSTRAINT sales_paid_amount_non_negative
            CHECK (paid_amount >= 0)
        ");

        DB::statement("
            ALTER TABLE sales
            ADD CONSTRAINT sales_due_amount_non_negative
            CHECK (due_amount >= 0)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

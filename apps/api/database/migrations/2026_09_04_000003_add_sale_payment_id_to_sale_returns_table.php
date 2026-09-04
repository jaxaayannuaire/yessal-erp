<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->foreignId('sale_payment_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('sale_payments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_payment_id');
        });
    }
};

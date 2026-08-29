<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained('terminals')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('opening_amount')->default(0);
            $table->bigInteger('expected_amount')->nullable();
            $table->bigInteger('counted_amount')->nullable();
            $table->bigInteger('variance_amount')->nullable();
            $table->text('variance_reason')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestampTz('opened_at');
            $table->timestampTz('closed_at')->nullable();
            $table->timestamps();

            $table->index(['terminal_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX cash_sessions_one_open_per_terminal ON cash_sessions (terminal_id) WHERE status = 'open'");
    }

    public function down(): void { Schema::dropIfExists('cash_sessions'); }
};

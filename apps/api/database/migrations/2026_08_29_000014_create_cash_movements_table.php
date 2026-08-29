<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('type', 40);
            $table->bigInteger('amount');
            $table->text('reason')->nullable();
            $table->string('reference', 150)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['organization_id', 'cash_session_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('cash_movements'); }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->string('entity_id', 100);
            $table->jsonb('before_payload')->nullable();
            $table->jsonb('after_payload')->nullable();
            $table->text('reason')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'action']);
        });
    }

    public function down(): void { Schema::dropIfExists('audit_logs'); }
};

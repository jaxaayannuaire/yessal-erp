<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('terminal_id')->nullable()->constrained('terminals')->nullOnDelete();
            $table->uuid('device_uuid');
            $table->string('name', 150)->nullable();
            $table->string('platform', 30)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('status', 30)->default('active');
            $table->timestampTz('last_seen_at')->nullable();
            $table->timestampTz('last_sync_at')->nullable();
            $table->timestampTz('paired_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'device_uuid']);
            $table->index(['organization_id', 'status']);
            $table->index(['terminal_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('devices'); }
};

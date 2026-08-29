<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnUpdate()->restrictOnDelete();
            $table->uuid('event_uuid');
            $table->string('entity_type', 100);
            $table->string('entity_id', 100);
            $table->string('action', 50);
            $table->jsonb('payload');
            $table->string('status', 30)->default('pending');
            $table->string('error_code', 100)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();

            $table->unique(['organization_id', 'event_uuid']);
            $table->index(['organization_id', 'status']);
            $table->index(['device_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('sync_events'); }
};

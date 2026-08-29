<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('entity_type', 100);
            $table->string('entity_id', 100);
            $table->string('local_version', 100)->nullable();
            $table->string('server_version', 100)->nullable();
            $table->string('conflict_type', 50);
            $table->jsonb('local_payload')->nullable();
            $table->jsonb('server_payload')->nullable();
            $table->string('resolution', 50)->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('sync_conflicts'); }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('entity_type', 100);
            $table->string('entity_id', 100);
            $table->string('operation', 20);
            $table->jsonb('payload');
            $table->timestampTz('occurred_at')->useCurrent();
            $table->foreignId('source_device_id')
                ->nullable()
                ->constrained('devices')
                ->nullOnDelete();

            $table->index(['organization_id', 'id']);
            $table->index(['organization_id', 'entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_changes');
    }
};

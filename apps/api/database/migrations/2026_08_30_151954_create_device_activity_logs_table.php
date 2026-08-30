<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('event_type', 50);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->jsonb('metadata')->nullable();

            $table->timestampTz('created_at')->useCurrent();

            $table->index([
                'organization_id',
                'device_id',
                'created_at',
            ]);

            $table->index([
                'device_id',
                'event_type',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_activity_logs');
    }
};
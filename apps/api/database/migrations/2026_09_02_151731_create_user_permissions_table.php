<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('permission_id')
                ->constrained('permissions')
                ->cascadeOnDelete();

            // true  = explicitement accordée
            // false = explicitement refusée
            $table->boolean('granted');

            $table->timestamps();

            $table->unique(
                ['organization_id', 'user_id', 'permission_id'],
                'user_permissions_unique'
            );

            $table->index(
                ['organization_id', 'user_id'],
                'user_permissions_user_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
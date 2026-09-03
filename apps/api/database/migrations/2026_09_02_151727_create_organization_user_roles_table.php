<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_user_roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['organization_id', 'user_id', 'role_id'],
                'organization_user_roles_unique'
            );

            $table->index(
                ['organization_id', 'user_id'],
                'organization_user_roles_user_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user_roles');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            // NULL = rôle système Yessal
            // ID  = rôle personnalisé d'une organisation
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['organization_id', 'slug'],
                'roles_organization_slug_unique'
            );

            $table->index(
                ['organization_id', 'is_active'],
                'roles_organization_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
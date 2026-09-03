<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();

            $table->string('module');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique('slug');

            $table->index(
                ['module', 'is_active'],
                'permissions_module_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
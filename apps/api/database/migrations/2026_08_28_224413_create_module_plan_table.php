<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_plan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('module_id')
                ->constrained('modules')
                ->cascadeOnDelete();

            $table->foreignId('plan_id')
                ->constrained('plans')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'module_id',
                'plan_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_plan');
    }
};
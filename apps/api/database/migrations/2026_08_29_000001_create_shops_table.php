<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('shops'); }
};

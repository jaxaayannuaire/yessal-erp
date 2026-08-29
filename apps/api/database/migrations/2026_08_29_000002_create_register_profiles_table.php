<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('register_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 150);
            $table->unsignedBigInteger('default_customer_id')->nullable();
            $table->jsonb('settings')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('register_profiles'); }
};

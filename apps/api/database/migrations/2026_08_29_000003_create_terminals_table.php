<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('register_profile_id')->nullable()->constrained('register_profiles')->nullOnDelete();
            $table->string('name', 150);
            $table->string('code', 50);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('terminals'); }
};

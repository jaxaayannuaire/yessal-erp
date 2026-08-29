<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180);
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->unique(['shop_id', 'slug']);
        });
    }

    public function down(): void { Schema::dropIfExists('categories'); }
};

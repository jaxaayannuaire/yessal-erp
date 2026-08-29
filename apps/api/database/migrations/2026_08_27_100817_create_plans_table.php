<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
	{
		Schema::create('plans', function (Blueprint $table) {
			$table->id();

			$table->string('name');
			$table->string('slug')->unique();

			$table->text('description')->nullable();

			$table->decimal('price_monthly', 12, 2)->default(0);
			$table->decimal('price_yearly', 12, 2)->nullable();

			$table->string('currency', 3)->default('XOF');

			$table->json('features')->nullable();

			$table->unsignedInteger('max_users')->nullable();
			$table->unsignedInteger('max_products')->nullable();

			$table->boolean('is_active')->default(true);
			$table->unsignedInteger('sort_order')->default(0);

			$table->timestamps();
		});
	}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

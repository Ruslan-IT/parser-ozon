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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ozon_product_id')->unique(); // ID товара на Ozon
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('seller')->nullable(); // продавец
            $table->decimal('current_price', 10, 2)->nullable();
            $table->integer('current_stock')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

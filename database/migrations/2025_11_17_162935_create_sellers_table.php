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
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();

            // ID продавца на Ozon
            $table->bigInteger('ozon_seller_id')->unique();

            // Название магазина
            $table->string('name');

            // Рейтинг продавца (если нужно)
            $table->decimal('rating', 3, 2)->nullable();

            // Количество отзывов
            $table->integer('reviews_count')->nullable();

            // URL магазина на Ozon
            $table->string('url')->nullable();

            // Тип продавца: Ozon / сторонний
            $table->enum('type', ['ozon', 'merchant'])->default('merchant');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};

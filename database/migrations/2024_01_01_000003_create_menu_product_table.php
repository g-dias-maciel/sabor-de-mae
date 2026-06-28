<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('day_of_week')->nullable()->comment('1=Segunda, 7=Domingo, null=Todos os dias');
            $table->timestamps();

            $table->unique(['menu_id', 'product_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_product');
    }
};

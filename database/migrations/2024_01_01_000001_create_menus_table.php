<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->date('start_date')->comment('Segunda-feira da semana de entrega');
            $table->date('end_date')->comment('Domingo da semana de entrega');
            $table->enum('status', ['planejamento', 'aberto', 'encerrado'])->default('planejamento');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};

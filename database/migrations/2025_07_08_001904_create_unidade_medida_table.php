<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnidadeMedidaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unidade_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->unsignedInteger('quantidade_unidade_minima')->default(1)->comment('Quantidade mínima da unidade');
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = Ativo, I = Inativo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unidade_medida');
    }
}

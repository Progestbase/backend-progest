<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSetoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = ativo, I = inativo');
            $table->enum('permissao', ['administrativo', 'solicitante'])->default('solicitante');
            $table->foreignId('unidade_id')->constrained('unidades')->onDelete('restrict');
            $table->timestamps();

            // Garantir que não existam setores com mesmo nome na mesma unidade
            $table->unique(['nome', 'unidade_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setores');
    }
}

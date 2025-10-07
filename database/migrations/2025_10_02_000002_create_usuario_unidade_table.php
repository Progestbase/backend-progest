<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioUnidadeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usuario_unidade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('unidade_id')->constrained('unidades')->onDelete('restrict');
            $table->enum('perfil', ['admin', 'almoxarife', 'solicitante']);
            $table->timestamps();

            // Garantir que um usuário não tenha perfis duplicados na mesma unidade
            $table->unique(['usuario_id', 'unidade_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('usuario_unidade');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioSetorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('usuario_setor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('setor_id')->constrained('setores')->onDelete('restrict');
            $table->enum('perfil', ['admin', 'almoxarife', 'solicitante']);
            $table->timestamps();

            // Garantir que um usuário não tenha perfis duplicados no mesmo setor
            $table->unique(['usuario_id', 'setor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('usuario_setor');
    }
}

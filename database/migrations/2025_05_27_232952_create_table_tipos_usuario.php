<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTableTiposUsuario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipos_usuario', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('descricao');
            $table->timestamps();
        });

        DB::table('tipos_usuario')->insert([
            ['nome' => 'Admin', 'descricao' => 'Administrador do Sistema'],
            ['nome' => 'Farmácia', 'descricao' => 'Responsável pela farmácia'],
            ['nome' => 'Almoxarife', 'descricao' => 'Responsável pelo almoxarifado'],
            ['nome' => 'Solicitante Farmácia', 'descricao' => 'Pode solicitar itens da farmácia'],
            ['nome' => 'Solicitante Almoxarifado', 'descricao' => 'Pode solicitar itens do almoxarifado'],
            ['nome' => 'Solicitante Geral', 'descricao' => 'Pode solicitar itens de ambos'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tipos_usuario');
    }
}

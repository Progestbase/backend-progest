<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTablePerfis extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('perfis', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('descricao');
            $table->enum('status', ['A','I'])->default('A')->comment('A = ativo, I = inativo');
            $table->timestamps();
        });

        DB::table('perfis')->insert([
            ['nome' => 'Admin', 'descricao' => 'Administrador do Sistema', 'status' => 'A'],
            ['nome' => 'Farmácia', 'descricao' => 'Responsável pela farmácia', 'status' => 'A'],
            ['nome' => 'Almoxarife', 'descricao' => 'Responsável pelo almoxarifado', 'status' => 'A'],
            ['nome' => 'Solicitante Farmácia', 'descricao' => 'Pode solicitar itens da farmácia', 'status' => 'A'],
            ['nome' => 'Solicitante Almoxarifado', 'descricao' => 'Pode solicitar itens do almoxarifado', 'status' => 'A'],
            ['nome' => 'Solicitante Geral', 'descricao' => 'Pode solicitar itens de ambos', 'status' => 'A'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('perfis');
    }
}

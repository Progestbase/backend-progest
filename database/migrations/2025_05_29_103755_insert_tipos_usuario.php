<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InsertTiposUsuario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('tipos_usuario')->insert([
            [
                'nome' => 'ADMIN',
                'descricao' => 'Administrador do sistema com acesso total',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'FARMÁCIA',
                'descricao' => 'Usuário com permissões para gerenciar farmácia',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'ALMOXARIFE',
                'descricao' => 'Usuário responsável pelo almoxarifado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'SOLICITANTE FARMÁCIA',
                'descricao' => 'Usuário que pode solicitar itens da farmácia',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'SOLICITANTE ALMOXARIFADO',
                'descricao' => 'Usuário que pode solicitar itens do almoxarifado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'SOLICITANTE GERAL',
                'descricao' => 'Usuário que pode solicitar itens tanto da farmácia quanto do almoxarifado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('tipos_usuario')->whereIn('nome', [
            'ADMIN',
            'FARMÁCIA',
            'ALMOXARIFE',
            'SOLICITANTE FARMÁCIA',
            'SOLICITANTE ALMOXARIFADO',
            'SOLICITANTE GERAL'
        ])->delete();
    }
}

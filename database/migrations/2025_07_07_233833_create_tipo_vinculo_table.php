<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class CreateTipoVinculoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipo_vinculo', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('descricao')->nullable();
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = Ativo, I = Inativo');
            $table->timestamps();
        });

        DB::table('tipo_vinculo')->insert([
            ['nome' => 'Efetivo', 'descricao' => 'Servidor Efetivo', 'status' => 'A'],
            ['nome' => 'Contrato', 'descricao' => 'Servidor Contratado', 'status' => 'A'],
            ['nome' => 'Temporário', 'descricao' => 'Servidor Temporário', 'status' => 'A'],
            ['nome' => 'Estagiário', 'descricao' => 'Servidor Estagiário', 'status' => 'A'],
            ['nome' => 'Terceirizado', 'descricao' => 'Servidor Terceirizado', 'status' => 'A'],
            ['nome' => 'Residente', 'descricao' => 'Servidor Residente', 'status' => 'A'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users')->where('email', 'admin@example.com')->delete();
        Schema::dropIfExists('tipo_vinculo');
    }
}

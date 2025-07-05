<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telefone')->after('email')->nullable();
            $table->string('matricula')->after('telefone')->unique();
            $table->date('data_nascimento')->after('matricula')->nullable();
            $table->string('cpf', 11)->after('data_nascimento')->unique();
            $table->enum('status', ['A','I'])->after('cpf')->default('A')->comment('A = ativo, I = inativo');
            $table->foreignId('unidade_consumidora_id')->after('status')->constrained('unidades_consumidoras');
            $table->unsignedBigInteger('usuario_tipo')->after('unidade_consumidora_id');
            $table->foreign('usuario_tipo')->references('id')->on('tipos_usuario');
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['usuario_tipo']);
            
            $table->dropColumn([
                'telefone', 
                'matricula', 
                'data_nascimento', 
                'cpf', 
                'status', 
                'unidade_consumidora_id',
                'usuario_tipo'
            ]);
        });
    }
}

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

            // Relacionamento com a tabela de Perfis do usuário
            $table->foreignId('perfil')->after('status')->constrained('perfis')->onDelete('restrict');

            // Relacionamento com a tabela dos tipos de vínculo
            $table->foreignId('tipo_vinculo')->after('perfil')->constrained('tipo_vinculo')->onDelete('restrict');

            // Relacionamento com a tabela setor
            $table->foreignId('setor')->after('tipo_vinculo')->constrained('setores')->onDelete('restrict');
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
            $table->dropForeign(['perfil']);
            $table->dropForeign(['tipo_vinculo']);
            $table->dropForeign(['setor']);

            $table->dropColumn([
                'telefone', 
                'matricula', 
                'data_nascimento', 
                'cpf', 
                'status', 
                'perfil',
                'tipo_vinculo',
                'setor'
            ]);
        });
    }
}

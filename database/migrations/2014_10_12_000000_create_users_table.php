<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('telefone')->nullable();
            $table->string('matricula')->unique();
            $table->date('data_nascimento')->nullable();
            $table->string('cpf', 11)->unique();
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = ativo, I = inativo');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Relacionamento com a tabela dos tipos de vínculo
            $table->foreignId('tipo_vinculo')->constrained('tipo_vinculo')->onDelete('restrict');

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
        Schema::dropIfExists('users');
    }
}

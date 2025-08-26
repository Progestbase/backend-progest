<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFornecedoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_pessoa', ['F', 'J'])->comment('F = Física, J = Jurídica');
            $table->string('razao_social_nome');
            $table->string('cpf', 11)->unique()->nullable();
            $table->string('cnpj', 14)->unique()->nullable();
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = ativo, I = inativo');
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
        Schema::dropIfExists('fornecedores');
    }
}

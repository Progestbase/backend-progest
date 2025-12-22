<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrupoProdutoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grupo_produto', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = Ativo, I = Inativo');
            $table->enum('tipo', ['Medicamento', 'Material'])->default('Material');
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
        Schema::dropIfExists('grupo_produto');
    }
}

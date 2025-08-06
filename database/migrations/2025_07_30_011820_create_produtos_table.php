<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProdutosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255)->nullable(false);
            $table->text('descricao')->nullable();
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = Ativo, I = Inativo');
            $table->string('codigo_produto', 50)->unique()->nullable(false);

            $table->unsignedBigInteger('unidade_medida_id');
            $table->foreign('unidade_medida_id')->references('id')->on('unidades_medida')->onDelete('restrict');

            $table->integer('quantidade_minima')->default(0)->comment('Quantidade mínima em estoque');
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
        Schema::table('produtos', function (Blueprint $table) {
            // Remove a foreign key primeiro para evitar erros
            $table->dropForeign(['unidade_medida_id']);
        });

        Schema::dropIfExists('produtos');
    }
}

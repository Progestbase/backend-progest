<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstoqueLoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estoque_lote', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidade_id')->constrained('setores')->onDelete('restrict');
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('restrict');
            $table->string('lote', 50);
            $table->decimal('quantidade_disponivel', 10, 3)->default(0);
            $table->date('data_vencimento');
            $table->date('data_fabricacao')->nullable();
            $table->timestamps();

            // Chave única composta: unidade + produto + lote
            $table->unique(['unidade_id', 'produto_id', 'lote'], 'unique_estoque_lote');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('estoque_lote');
    }
}

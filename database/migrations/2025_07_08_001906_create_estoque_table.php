<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstoqueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('estoque', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('restrict');
            $table->foreignId('setor_id')->constrained('setores')->onDelete('restrict');
            $table->integer('quantidade_atual');
            $table->integer('quantidade_minima');
            $table->enum('status_disponibilidade', ['D', 'I'])->default('D')->comment('D = Disponível, I = Indisponível');
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
        Schema::dropIfExists('estoque');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSetorFornecedorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setor_fornecedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setor_solicitante_id')->constrained('setores')->onDelete('restrict');
            $table->foreignId('setor_fornecedor_id')->constrained('setores')->onDelete('restrict');
            $table->timestamps();

            // Garantir que um setor tenha apenas uma vez o mesmo fornecedor
            $table->unique(['setor_solicitante_id', 'setor_fornecedor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setor_fornecedor');
    }
}

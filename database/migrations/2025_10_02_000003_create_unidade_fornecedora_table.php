<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnidadeFornecedoraTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unidade_fornecedora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidade_solicitante_id')->constrained('unidades')->onDelete('restrict');
            $table->foreignId('unidade_fornecedora_id')->constrained('unidades')->onDelete('restrict');
            $table->enum('tipo_produto', ['Medicamento', 'Material']);
            $table->timestamps();

            // Garantir que uma unidade tenha apenas uma fornecedora por tipo de produto
            $table->unique(['unidade_solicitante_id', 'tipo_produto']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unidade_fornecedora');
    }
}

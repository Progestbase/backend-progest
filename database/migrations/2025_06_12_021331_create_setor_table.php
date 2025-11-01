<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSetorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidade_id')->constrained('unidades')->onDelete('restrict');
            $table->string('nome');
            $table->string('descricao')->nullable();
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = Ativo, I = Inativo');
            $table->boolean('estoque')->default(false);
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
        Schema::dropIfExists('setores');
    }
}

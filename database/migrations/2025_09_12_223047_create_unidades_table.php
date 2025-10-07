<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnidadesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('central', ['S', 'N'])->default('N')->comment('S = Sim, N = Não');
            $table->enum('status', ['A', 'I'])->default('A')->comment('A = Ativo, I = Inativo');
            $table->enum('estoque', ['S', 'N'])->default('N')->comment('S = Sim, N = Não');
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
        Schema::dropIfExists('unidades');
    }
}

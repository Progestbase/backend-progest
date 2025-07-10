<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnidadesMedidaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unidades_medida', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255)->nullable(false);
            $table->text('descricao')->nullable();
            $table->string('status')->default('A')->comment('A = ativo, I = inativo');
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
        Schema::dropIfExists('unidades_medida');
    }
} 
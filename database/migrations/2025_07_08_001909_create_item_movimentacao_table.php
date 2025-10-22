<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemMovimentacaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_movimentacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movimentacao_id')->constrained('movimentacao')->onDelete('restrict');
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('restrict');
            $table->integer('quantidade_solicitada');
            $table->integer('quantidade_liberada');
            $table->string('lote')->nullable();
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
        Schema::dropIfExists('item_movimentacao');
    }
}

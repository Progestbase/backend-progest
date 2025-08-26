<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItensEntradaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('itens_entrada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrada_id')->constrained('entrada')->onDelete('restrict');
            $table->foreignId('produto_id')->constrained('produtos')->onDelete('restrict');
            $table->string('lote');
            $table->decimal('valor_unitario', 15, 2);
            $table->integer('quantidade');
            $table->date('data_fabricacao')->nullable();
            $table->date('data_vencimento')->nullable();
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
        Schema::dropIfExists('itens_entrada');
    }
}

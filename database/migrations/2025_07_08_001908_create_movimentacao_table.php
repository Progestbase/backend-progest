<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMovimentacaoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movimentacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('unidade_origem_id')->nullable()->constrained('unidades')->onDelete('restrict');
            $table->foreignId('unidade_destino_id')->nullable()->constrained('unidades')->onDelete('restrict');
            $table->enum('tipo', ['T', 'D', 'S'])->comment('T = Transferência, D = Devolução, S = Saída');
            $table->dateTime('data_hora');
            $table->text('observacao')->nullable();
            $table->enum('status_solicitacao', ['R', 'P'])->default('P')->comment('R = Resolvido, P = Pendente');
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
        Schema::dropIfExists('movimentacao');
    }
}

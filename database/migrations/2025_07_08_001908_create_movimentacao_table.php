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
            $table->foreignId('setor_origem_id')->nullable()->constrained('setores')->onDelete('restrict');
            $table->foreignId('setor_destino_id')->nullable()->constrained('setores')->onDelete('restrict');
            $table->enum('tipo', ['T', 'D', 'S'])->comment('T = Transferência, D = Devolução, S = Saída');
            $table->dateTime('data_hora');
            $table->text('observacao')->nullable();
            // Status: A = Aprovado, R = Reprovado, P = Pendente, C = Rascunho
            $table->enum('status_solicitacao', ['A', 'R', 'P', 'C'])->default('P')->comment('A=Aprovado,R=Reprovado,P=Pendente,C=Rascunho');
            $table->foreignId('aprovador_usuario_id')->nullable()->constrained('users')->onDelete('restrict');
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

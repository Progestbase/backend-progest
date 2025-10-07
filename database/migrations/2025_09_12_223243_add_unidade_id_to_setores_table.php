<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnidadeIdToSetoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('setores', function (Blueprint $table) {
            $table->foreignId('unidade_id')->constrained('unidades')->onDelete('restrict')->after('id');
            $table->dropColumn('estoque');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('setores', function (Blueprint $table) {
            $table->enum('estoque', ['S', 'N'])->default('N')->comment('S = Sim, N = Não');
            $table->dropForeign(['unidade_id']);
            $table->dropColumn('unidade_id');
        });
    }
}

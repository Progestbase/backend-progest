<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // ID automático
            $table->unsignedBigInteger('user_id'); // Referência ao usuário
            $table->text('justification')->nullable();
            $table->string('password'); // Senha
            $table->enum('status', ['PENDING', 'CANCELED', 'COMPLETED', 'WAITING CONFIRMATION'])->default('PENDING'); // Status do pedido
            $table->timestamp('order_date')->useCurrent();
            $table->timestamps();

            // Adicionando as chaves estrangeiras
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}

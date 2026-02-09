<?php

namespace App\Observers;

use App\Models\Estoque;

class EstoqueObserver
{
    /**
     * Handle the Estoque "saving" event.
     * Este evento roda ANTES de inserir ou atualizar no banco.
     *
     * @param  \App\Models\Estoque  $estoque
     * @return void
     */
    public function saving(Estoque $estoque)
    {
        if ($estoque->quantidade_atual <= 0) {
            $estoque->status_disponibilidade = 'I';
        } elseif ($estoque->quantidade_atual > 0) {
            $estoque->status_disponibilidade = 'D';
        }
    }
}
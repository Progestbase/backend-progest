<?php

namespace App\Observers;

use App\Models\Setores;
use App\Models\Estoque;

class SetoresObserver
{
    /**
     * Handle the Setores "created" event.
     */
    public function created(Setores $setor)
    {
        // Se o setor criado possui estoque, criar estoque inicial para todos os produtos compatíveis
        if ($setor->estoque) {
            Estoque::criarEstoqueInicialParaSetor($setor->id);
        }
    }

    /**
     * Handle the Setores "updated" event.
     */
    public function updated(Setores $setor)
    {
        // Se o setor foi alterado para ter estoque, criar estoque inicial
        if ($setor->estoque && $setor->wasChanged('estoque')) {
            Estoque::criarEstoqueInicialParaSetor($setor->id);
        }
    }
}

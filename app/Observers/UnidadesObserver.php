<?php

namespace App\Observers;

use App\Models\Unidades;
use App\Models\Estoque;

class UnidadesObserver
{
    /**
     * Handle the Unidades "created" event.
     */
    public function created(Unidades $unidade)
    {
        // Se a unidade criada possui estoque, criar estoque inicial para todos os produtos compatíveis
        if ($unidade->estoque) {
            Estoque::criarEstoqueInicialParaUnidade($unidade->id);
        }
    }

    /**
     * Handle the Unidades "updated" event.
     */
    public function updated(Unidades $unidade)
    {
        // Se a unidade foi alterada para ter estoque, criar estoque inicial
        if ($unidade->estoque && $unidade->wasChanged('estoque')) {
            Estoque::criarEstoqueInicialParaUnidade($unidade->id);
        }
    }
}

<?php

namespace App\Observers;

use App\Models\Estoque;
use Illuminate\Support\Facades\Log;

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
        // CRÍTICO: Prevenir quantidade negativa a nível de aplicação
        if ($estoque->quantidade_atual < 0) {
            Log::error("Tentativa de salvar estoque com quantidade negativa", [
                'estoque_id' => $estoque->id,
                'produto_id' => $estoque->produto_id,
                'unidade_id' => $estoque->unidade_id,
                'quantidade_atual' => $estoque->quantidade_atual,
            ]);
            
            throw new \RuntimeException(
                "Quantidade de estoque não pode ser negativa. " .
                "Produto: {$estoque->produto_id}, Setor: {$estoque->unidade_id}, Quantidade: {$estoque->quantidade_atual}"
            );
        }

        // Atualizar status de disponibilidade automaticamente
        if ($estoque->quantidade_atual <= 0) {
            $estoque->status_disponibilidade = 'I';
        } elseif ($estoque->quantidade_atual > 0) {
            $estoque->status_disponibilidade = 'D';
        }

        // Log de alteração significativa
        if ($estoque->exists && $estoque->isDirty('quantidade_atual')) {
            $original = $estoque->getOriginal('quantidade_atual');
            $diferenca = $estoque->quantidade_atual - $original;
            
            Log::info("Alteração de estoque detectada", [
                'estoque_id' => $estoque->id,
                'produto_id' => $estoque->produto_id,
                'unidade_id' => $estoque->unidade_id,
                'quantidade_anterior' => $original,
                'quantidade_nova' => $estoque->quantidade_atual,
                'diferenca' => $diferenca,
            ]);
        }
    }

    /**
     * Handle the Estoque "created" event.
     *
     * @param  \App\Models\Estoque  $estoque
     * @return void
     */
    public function created(Estoque $estoque)
    {
        Log::info("Novo estoque criado", [
            'estoque_id' => $estoque->id,
            'produto_id' => $estoque->produto_id,
            'unidade_id' => $estoque->unidade_id,
            'quantidade_atual' => $estoque->quantidade_atual,
        ]);
    }
}

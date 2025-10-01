<?php

namespace App\Observers;

use App\Models\Produto;
use App\Models\Estoque;

class ProdutoObserver
{
    /**
     * Handle the Produto "created" event.
     */
    public function created(Produto $produto)
    {
        // Quando um produto é criado, instanciar estoque em todas as unidades compatíveis
        Estoque::criarEstoqueParaNovoProduto($produto->id);
    }

    /**
     * Handle the Produto "deleted" event.
     */
    public function deleted(Produto $produto)
    {
        // Quando um produto é deletado, remover todos os estoques relacionados
        Estoque::where('produto_id', $produto->id)->delete();
    }
}

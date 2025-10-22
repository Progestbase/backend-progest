<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemMovimentacao extends Model
{
    protected $table = 'item_movimentacao';
    protected $fillable = ['movimentacao_id', 'produto_id', 'quantidade_solicitada', 'quantidade_liberada', 'lote'];
    public function movimentacao()
    {
        return $this->belongsTo(Movimentacao::class);
    }
    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function estoqueLote()
    {
        return $this->belongsTo(EstoqueLote::class, 'lote');
    }
}

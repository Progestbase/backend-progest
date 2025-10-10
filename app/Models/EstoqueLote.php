<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstoqueLote extends Model
{
    use HasFactory;

    protected $table = 'estoque_lote';

    protected $fillable = [
        'unidade_id',
        'produto_id',
        'lote',
        'quantidade_disponivel',
        'data_vencimento',
        'data_fabricacao',
    ];

    protected $casts = [
        'quantidade_disponivel' => 'decimal:3',
        'data_vencimento' => 'date',
        'data_fabricacao' => 'date',
    ];

    /**
     * Relacionamento com Unidades
     */
    public function unidade()
    {
        return $this->belongsTo(Unidades::class, 'unidade_id');
    }

    /**
     * Relacionamento com Produto
     */
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Scope para filtrar por unidade
     */
    public function scopePorUnidade($query, $unidadeId)
    {
        return $query->where('unidade_id', $unidadeId);
    }

    /**
     * Scope para filtrar por produto
     */
    public function scopePorProduto($query, $produtoId)
    {
        return $query->where('produto_id', $produtoId);
    }

    /**
     * Scope para filtrar lotes disponíveis (quantidade > 0)
     */
    public function scopeDisponiveis($query)
    {
        return $query->where('quantidade_disponivel', '>', 0);
    }

    /**
     * Scope para filtrar lotes vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('data_vencimento', '<', now());
    }

    /**
     * Scope para filtrar lotes próximos ao vencimento
     */
    public function scopeProximosVencimento($query, $dias = 30)
    {
        return $query->whereBetween('data_vencimento', [now(), now()->addDays($dias)]);
    }

    /**
     * Scope para ordenar por FEFO (First Expire, First Out)
     */
    public function scopeFEFO($query)
    {
        return $query->orderBy('data_vencimento', 'asc');
    }

    /**
     * Scope para ordenar por FIFO (First In, First Out)
     */
    public function scopeFIFO($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}

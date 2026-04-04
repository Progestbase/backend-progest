<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstoqueAuditoria extends Model
{
    protected $table = 'estoque_auditoria';
    
    public $timestamps = false; // A tabela usa apenas data_hora
    
    protected $fillable = [
        'estoque_id',
        'produto_id',
        'unidade_id',
        'quantidade_anterior',
        'quantidade_nova',
        'diferenca',
        'operacao',
        'usuario',
    ];

    protected $casts = [
        'quantidade_anterior' => 'integer',
        'quantidade_nova' => 'integer',
        'diferenca' => 'integer',
        'data_hora' => 'datetime',
    ];

    // Relacionamentos
    public function estoque()
    {
        return $this->belongsTo(Estoque::class, 'estoque_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function setor()
    {
        return $this->belongsTo(Setores::class, 'unidade_id');
    }

    // Scopes
    public function scopePorProduto($query, $produtoId)
    {
        return $query->where('produto_id', $produtoId);
    }

    public function scopePorSetor($query, $setorId)
    {
        return $query->where('unidade_id', $setorId);
    }

    public function scopePorPeriodo($query, $dataInicio, $dataFim)
    {
        return $query->whereBetween('data_hora', [$dataInicio, $dataFim]);
    }

    public function scopeIncremento($query)
    {
        return $query->where('diferenca', '>', 0);
    }

    public function scopeDecremento($query)
    {
        return $query->where('diferenca', '<', 0);
    }
}

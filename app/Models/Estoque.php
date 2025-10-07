<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    use HasFactory;

    protected $table = 'estoque';

    protected $fillable = [
        'produto_id',
        'unidade_id',
        'quantidade_atual',
        'quantidade_minima',
        'status_disponibilidade',
    ];

    protected $casts = [
        'quantidade_atual' => 'integer',
        'quantidade_minima' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function unidade()
    {
        return $this->belongsTo(Unidades::class, 'unidade_id');
    }

    // Scopes
    public function scopeDisponivel($query)
    {
        return $query->where('status_disponibilidade', 'D');
    }

    public function scopeIndisponivel($query)
    {
        return $query->where('status_disponibilidade', 'I');
    }

    public function scopePorUnidade($query, $unidadeId)
    {
        return $query->where('unidade_id', $unidadeId);
    }

    // Métodos auxiliares
    public function isDisponivel()
    {
        return $this->status_disponibilidade === 'D';
    }

    public function isAbaixoMinimo()
    {
        return $this->quantidade_atual < $this->quantidade_minima;
    }

    /**
     * Método estático para criar estoque inicial para todos os produtos de um tipo em uma unidade
     */
    public static function criarEstoqueInicialParaUnidade($unidadeId)
    {
        $unidade = Unidades::find($unidadeId);

        if (!$unidade || !$unidade->estoque) {
            return;
        }

        // Buscar todos os produtos do tipo compatível com a unidade
        $produtos = Produto::whereHas('grupoProduto', function ($query) use ($unidade) {
            $query->where('tipo', $unidade->tipo)->where('status', 'A');
        })->where('status', 'A')->get();

        foreach ($produtos as $produto) {
            // Verificar se já existe estoque para este produto nesta unidade
            $estoqueExistente = self::where('produto_id', $produto->id)
                ->where('unidade_id', $unidadeId)
                ->first();

            if (!$estoqueExistente) {
                self::create([
                    'produto_id' => $produto->id,
                    'unidade_id' => $unidadeId,
                    'quantidade_atual' => 0,
                    'quantidade_minima' => 0,
                    'status_disponibilidade' => 'D'
                ]);
            }
        }
    }

    /**
     * Método estático para criar estoque em todas as unidades compatíveis quando um produto é criado
     */
    public static function criarEstoqueParaNovoProduto($produtoId)
    {
        $produto = Produto::with('grupoProduto')->find($produtoId);

        if (!$produto || !$produto->grupoProduto) {
            return;
        }

        // Buscar todas as unidades que têm estoque e são do tipo compatível
        $unidades = Unidades::where('estoque', true)
            ->where('tipo', $produto->grupoProduto->tipo)
            ->where('status', 'A')
            ->get();

        foreach ($unidades as $unidade) {
            // Verificar se já existe estoque para este produto nesta unidade
            $estoqueExistente = self::where('produto_id', $produtoId)
                ->where('unidade_id', $unidade->id)
                ->first();

            if (!$estoqueExistente) {
                self::create([
                    'produto_id' => $produtoId,
                    'unidade_id' => $unidade->id,
                    'quantidade_atual' => 0,
                    'quantidade_minima' => 0,
                    'status_disponibilidade' => 'D'
                ]);
            }
        }
    }
}

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
        'localizacao',
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

    public function setor()
    {
        return $this->belongsTo(Setores::class, 'unidade_id');
    }

    public function lotes()
    {
        return $this->hasMany(EstoqueLote::class, 'unidade_id', 'unidade_id')
            ->where('produto_id', $this->produto_id);
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

    public function scopePorSetor($query, $setorId)
    {
        return $query->where('unidade_id', $setorId);
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
     * Método estático para criar estoque inicial para todos os produtos de um tipo em um setor
     */
    public static function criarEstoqueInicialParaSetor($setorId)
    {
        $setor = Setores::find($setorId);

        if (!$setor || !$setor->estoque) {
            return;
        }

        // Buscar todos os produtos do tipo compatível com o setor
        $produtos = Produto::whereHas('grupoProduto', function ($query) use ($setor) {
            $query->where('tipo', $setor->tipo)->where('status', 'A');
        })->where('status', 'A')->get();

        foreach ($produtos as $produto) {
            // Verificar se já existe estoque para este produto neste setor
            $estoqueExistente = self::where('produto_id', $produto->id)
                ->where('unidade_id', $setorId)
                ->first();

            if (!$estoqueExistente) {
                self::create([
                    'produto_id' => $produto->id,
                    'unidade_id' => $setorId,
                    'quantidade_atual' => 0,
                    'quantidade_minima' => 0,
                    'status_disponibilidade' => 'D'
                ]);
            }
        }
    }

    /**
     * Método estático para criar estoque em todas os setores compatíveis quando um produto é criado
     */
    public static function criarEstoqueParaNovoProduto($produtoId)
    {
        $produto = Produto::with('grupoProduto')->find($produtoId);

        if (!$produto || !$produto->grupoProduto) {
            return;
        }

        // Buscar todas os setores que têm estoque e são do tipo compatível
        $setores = Setores::where('estoque', true)
            ->where('tipo', $produto->grupoProduto->tipo)
            ->where('status', 'A')
            ->get();

        foreach ($setores as $setor) {
            // Verificar se já existe estoque para este produto neste setor
            $estoqueExistente = self::where('produto_id', $produtoId)
                ->where('unidade_id', $setor->id)
                ->first();

            if (!$estoqueExistente) {
                self::create([
                    'produto_id' => $produtoId,
                    'unidade_id' => $setor->id,
                    'quantidade_atual' => 0,
                    'quantidade_minima' => 0,
                    'status_disponibilidade' => 'D'
                ]);
            }
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidades extends Model
{
    use HasFactory;

    protected $table = 'unidades';

    protected $fillable = [
        'polo_id',
        'nome',
        'descricao',
        'status',
        'estoque',
        'tipo'
    ];

    protected $casts = [
        'estoque' => 'boolean',
    ];

    /**
     * Relacionamento com polo
     */
    public function polo()
    {
        return $this->belongsTo(Polo::class, 'polo_id');
    }

    /**
     * Obter produtos disponíveis para esta unidade baseado no tipo
     */
    public function produtosDisponiveis()
    {
        return Produto::whereHas('grupoProduto', function ($query) {
            $query->where('tipo', $this->tipo);
        });
    }

    /**
     * Obter grupos de produtos compatíveis com esta unidade
     */
    public function gruposCompatíveis()
    {
        return GrupoProduto::where('tipo', $this->tipo)->where('status', 'A');
    }

    /**
     * Relacionamento com estoque
     */
    public function estoques()
    {
        return $this->hasMany(Estoque::class, 'unidade_id');
    }

    /**
     * Relacionamento com usuários
     */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'unidade');
    }
}

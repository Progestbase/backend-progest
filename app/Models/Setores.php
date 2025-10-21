<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setores extends Model
{
    use HasFactory;

    protected $table = 'setores';

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
     * Obter produtos disponíveis para este setor baseado no tipo
     */
    public function produtosDisponiveis()
    {
        return Produto::whereHas('grupoProduto', function ($query) {
            $query->where('tipo', $this->tipo);
        });
    }

    /**
     * Obter grupos de produtos compatíveis com este setor
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
        // Relacionamento many-to-many via tabela pivot 'setor_user'
        return $this->belongsToMany(User::class, 'setor_user', 'setor_id', 'user_id');
    }

    /**
     * Fornecedores relacionados a este setor (como solicitante)
     */
    public function fornecedoresRelacionados()
    {
        return $this->hasMany(SetorFornecedor::class, 'setor_solicitante_id');
    }
}

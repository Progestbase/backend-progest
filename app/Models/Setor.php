<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setor extends Model
{
    protected $table = 'setores';
    protected $fillable = ['nome', 'status', 'permissao', 'unidade_id'];

    /**
     * Relacionamento com Unidades (many-to-one)
     * Um setor pertence a uma unidade
     */
    public function unidade()
    {
        return $this->belongsTo(Unidades::class, 'unidade_id');
    }

    /**
     * Relacionamento com Users (many-to-many)
     * Um setor pode ter vários usuários
     */
    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'usuario_setor', 'setor_id', 'user_id')
            ->withTimestamps();
    }
}

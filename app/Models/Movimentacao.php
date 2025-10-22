<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model
{
    protected $table = 'movimentacao';
    protected $fillable = ['usuario_id', 'setor_origem_id', 'setor_destino_id', 'tipo', 'data_hora', 'observacao', 'status_solicitacao', 'aprovador_usuario_id'];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function setorOrigem()
    {
        return $this->belongsTo(Setores::class, 'setor_origem_id');
    }

    public function setorDestino()
    {
        return $this->belongsTo(Setores::class, 'setor_destino_id');
    }

    public function itens()
    {
        return $this->hasMany(ItemMovimentacao::class);
    }

    public function aprovador()
    {
        return $this->belongsTo(User::class, 'aprovador_usuario_id');
    }
}

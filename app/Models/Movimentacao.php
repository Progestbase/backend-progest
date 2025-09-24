<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model
{
    protected $table = 'movimentacao';
    protected $fillable = ['usuario_id', 'paciente_id', 'unidade_origem_id', 'unidade_destino_id', 'tipo', 'data_hora', 'observacao', 'status_solicitacao'];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function unidadeOrigem()
    {
        return $this->belongsTo(Unidades::class, 'unidade_origem_id');
    }

    public function unidadeDestino()
    {
        return $this->belongsTo(Unidades::class, 'unidade_destino_id');
    }

    public function itens()
    {
        return $this->hasMany(ItemMovimentacao::class);
    }
}

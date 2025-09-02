<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model
{
    protected $table = 'movimentacao';
    protected $fillable = ['usuario_id', 'paciente_id', 'setor_origem_id', 'setor_destino_id', 'tipo', 'data_hora', 'observacao', 'status_solicitacao'];
    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
    public function setorOrigem()
    {
        return $this->belongsTo(Setor::class, 'setor_origem_id');
    }
    public function setorDestino()
    {
        return $this->belongsTo(Setor::class, 'setor_destino_id');
    }
    public function itens()
    {
        return $this->hasMany(ItemMovimentacao::class);
    }
}

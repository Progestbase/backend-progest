<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    protected $table = 'entrada';
    protected $fillable = ['nota_fiscal', 'unidade_id', 'fornecedor_id'];

    public function setor()
    {
        return $this->belongsTo(Setores::class, 'unidade_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }
    public function itens()
    {
        return $this->hasMany(ItensEntrada::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    protected $table = 'entrada';
    protected $fillable = ['nota_fiscal', 'data_emissao_nota_fiscal', 'data_hora', 'fornecedor_id'];
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }
    public function itens()
    {
        return $this->hasMany(ItensEntrada::class);
    }
}

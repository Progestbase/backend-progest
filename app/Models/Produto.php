<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produtos';
    protected $fillable = ['nome', 'marca', 'codigo_simpras', 'codigo_barras', 'grupo_produto_id', 'unidade_medida_id', 'status'];

    public function grupoProduto()
    {
        return $this->belongsTo(GrupoProduto::class, 'grupo_produto_id');
    }

    public function unidadeMedida()
    {
        return $this->belongsTo(UnidadeMedida::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produtos';
    protected $fillable = ['nome', 'marca', 'codigo_simpras', 'codigo_barras', 'tipo_produto_id', 'unidade_medida_id', 'status'];
    public function tipoProduto()
    {
        return $this->belongsTo(TipoProduto::class);
    }
    public function unidadeMedida()
    {
        return $this->belongsTo(UnidadeMedida::class);
    }
}

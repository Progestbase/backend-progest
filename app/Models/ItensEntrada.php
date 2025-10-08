<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItensEntrada extends Model
{
    protected $table = 'itens_entrada';
    protected $fillable = ['entrada_id', 'produto_id', 'quantidade'];
    public function entrada()
    {
        return $this->belongsTo(Entrada::class);
    }
    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }
}

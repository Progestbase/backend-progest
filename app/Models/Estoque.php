<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    protected $table = 'estoque'; // Garante que usa a tabela correta

    protected $fillable = [
        'produto_id',
        'unidade_id',
        'quantidade',
        'status',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function unidade()
    {
        return $this->belongsTo(Unidades::class, 'unidade_id');
    }
}

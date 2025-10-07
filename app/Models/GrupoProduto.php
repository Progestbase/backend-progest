<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoProduto extends Model
{
    use HasFactory;

    protected $table = 'grupo_produto';

    protected $fillable = [
        'nome',
        'status',
        'tipo'
    ];

    public function produtos()
    {
        return $this->hasMany(Produto::class, 'grupo_produto_id');
    }
}

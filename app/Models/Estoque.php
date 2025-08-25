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
}

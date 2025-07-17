<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    protected $table = 'fornecedores'; // Garante que usa a tabela correta

    protected $fillable = [
        'codigo',
        'cnpj',
        'razao_social',
        'status',
    ];
}

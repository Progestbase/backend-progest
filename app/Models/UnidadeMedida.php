<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadeMedida extends Model
{
    protected $table = 'unidade_medida';
    protected $fillable = ['nome', 'quantidade_unidade_minima', 'status'];
}

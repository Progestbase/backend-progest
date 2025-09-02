<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoVinculo extends Model
{
    protected $table = 'tipo_vinculo';
    protected $fillable = ['nome', 'descricao', 'status'];
}

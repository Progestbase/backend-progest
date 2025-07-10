<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriasProdutos extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

     protected $table = 'categorias_produtos';

     protected $fillable = [
         'nome',
         'descricao',
         'status',
     ];
 
     /**
      * The attributes that should be hidden for serialization.
      *
      * @var array<int, string>
      */
     protected $hidden = [
         'created_at',
         'updated_at',
     ];
}   
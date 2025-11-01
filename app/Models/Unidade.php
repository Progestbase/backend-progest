<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidade extends Model
{
    use HasFactory;

    protected $table = 'unidades';

    protected $fillable = [
        'nome',
        'status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos
    public function setores()
    {
        return $this->hasMany(Setores::class, 'unidade_id');
    }

    // Scopes para filtros
    public function scopeAtivo($query)
    {
        return $query->where('status', 'A');
    }

    public function scopeInativo($query)
    {
        return $query->where('status', 'I');
    }

    // Accessors
    public function isAtivo()
    {
        return $this->status === 'A';
    }
}

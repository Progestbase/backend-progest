<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetorFornecedor extends Model
{
    use HasFactory;

    protected $table = 'setor_fornecedor';

    protected $fillable = [
        'setor_solicitante_id',
        'setor_fornecedor_id',
        'tipo_produto'
    ];

    public function solicitante()
    {
        return $this->belongsTo(Setores::class, 'setor_solicitante_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Setores::class, 'setor_fornecedor_id');
    }
}

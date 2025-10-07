<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    use HasFactory;

    protected $table = 'fornecedores';

    protected $fillable = [
        'tipo_pessoa',
        'razao_social_nome',
        'cpf',
        'cnpj',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Accessor para determinar se é pessoa física ou jurídica
    public function isPessoaFisica()
    {
        return $this->tipo_pessoa === 'F';
    }

    public function isPessoaJuridica()
    {
        return $this->tipo_pessoa === 'J';
    }

    // Accessor para obter o documento (CPF ou CNPJ)
    public function getDocumentoAttribute()
    {
        return $this->isPessoaFisica() ? $this->cpf : $this->cnpj;
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

    public function scopePessoaFisica($query)
    {
        return $query->where('tipo_pessoa', 'F');
    }

    public function scopePessoaJuridica($query)
    {
        return $query->where('tipo_pessoa', 'J');
    }
}

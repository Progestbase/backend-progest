<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class TipoVinculoRequest extends BaseFormRequest
{
    public function rules()
    {
        $data = $this->input('tipoVinculo', $this->all());
        $id = $data['id'] ?? null;

        $rules = [
            'tipoVinculo.nome' => 'required|string|min:3|max:191|unique:tipo_vinculo,nome,' . $id,
            'tipoVinculo.descricao' => 'nullable|string|max:191',
            'tipoVinculo.status' => 'required|in:A,I',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'tipoVinculo.nome.required' => 'O nome do Tipo Vinculo é obrigatório.',
            'tipoVinculo.status.required' => 'O status do fornecedor é obrigatório.',

            'tipoVinculo.nome.min' => 'O nome do Tipo Vinculo deve ter no mínimo 3 caracteres.',
            'tipoVinculo.nome.max' => 'O nome do Tipo Vinculo deve ter no máximo 191 caracteres.',
            'tipoVinculo.descricao.max' => 'A descrição do Tipo Vinculo deve ter no máximo 191 caracteres.',

            'tipoVinculo.status.in' => 'Status deve ser A (Ativo) ou I (Inativo)',
            'tipoVinculo.nome.unique' => 'Este Tipo de Vinculo já está cadastrado.',
        ];
    }

    public function attributes()
    {
        return [
            'tipoVinculo.nome' => 'Nome',
            'tipoVinculo.descricao' => 'Descrição',
            'tipoVinculo.status' => 'Status'
        ];
    }
}

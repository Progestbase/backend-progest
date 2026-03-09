<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class GrupoProdutoRequest extends BaseFormRequest
{
    public function rules()
    {
        $data = $this->input('grupoProduto', $this->all());
        $id = $data['id'] ?? null;

        $rules = [
            'grupoProduto.id' => $id ? 'required|integer|exists:grupo_produto,id' : 'nullable',
            'grupoProduto.nome' => 'required|string|min:3|max:191|unique:grupo_produto,nome,' . $id,
            'grupoProduto.tipo' => 'required|in:Medicamento,Material',
            'grupoProduto.status' => 'required|in:A,I',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'grupoProduto.nome.required' => 'O nome do Grupo de Produtos é obrigatório.',
            'grupoProduto.tipo.required' => 'O Tipo de Produto do Grupo de Produtos é obrigatório.',
            'grupoProduto.status.required' => 'O status do Grupo de Produtos é obrigatório.',

            'grupoProduto.nome.min' => 'O nome do Grupo de Produtos deve ter no mínimo 3 caracteres.',
            'grupoProduto.nome.max' => 'O nome do Grupo de Produtos deve ter no máximo 191 caracteres.',

            'grupoProduto.status.in' => 'Status deve ser A (Ativo) ou I (Inativo)',
            'grupoProduto.tipo.in' => 'Tipo de Produto deve ser Medicamento ou Material',
            'grupoProduto.nome.unique' => 'Este Grupo de Produto já está cadastrado.'
        ];
    }

    public function attributes()
    {
        return [
            'grupoProduto.nome' => 'Nome',
            'grupoProduto.tipo' => 'Tipo de Produto',
            'grupoProduto.status' => 'Status'
        ];
    }
}

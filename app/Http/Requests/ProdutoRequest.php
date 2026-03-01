<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class ProdutoRequest extends BaseFormRequest
{
    public function rules()
    {
        // O frontend envia os dados dentro do objeto "produto"
        $produto = $this->input('produto', []);
        $id = $produto['id'] ?? null;

        return [
            'produto.id' => 'nullable|integer',
            'produto.nome' => 'required|string|max:191',
            'produto.marca' => 'nullable|string|max:191',
            'produto.codigo_simpras' => 'nullable|string|max:191|unique:produtos,codigo_simpras,' . $id,
            'produto.codigo_barras' => 'nullable|string|max:191|unique:produtos,codigo_barras,' . $id,
            'produto.grupo_produto_id' => 'required|exists:grupo_produto,id',
            'produto.unidade_medida_id' => 'required|exists:unidade_medida,id',
            'produto.status' => 'nullable|in:A,I'
        ];
    }

    public function messages()
    {
        return [
            'produto.nome.required' => 'O nome do produto é obrigatório',
            'produto.grupo_produto_id.required' => 'O grupo do produto é obrigatório',
            'produto.unidade_medida_id.required' => 'A unidade de medida é obrigatória',

            'produto.nome.max' => 'O nome não pode ter mais de 191 caracteres',
            'produto.marca.max' => 'A marca não pode ter mais de 191 caracteres',

            'produto.codigo_simpras.unique' => 'Este código SIMPRAS já está cadastrado',
            'produto.codigo_barras.unique' => 'Este código de barras já está cadastrado',
            'produto.grupo_produto_id.exists' => 'Grupo de produto não encontrado',
            'produto.unidade_medida_id.exists' => 'Unidade de medida não encontrada',
            'produto.status.in' => 'Status deve ser A (Ativo) ou I (Inativo)'
        ];
    }

    public function attributes()
    {
        return [
            'produto.nome' => 'Nome do Produto',
            'produto.marca' => 'Marca do Produto',
            'produto.codigo_simpras' => 'Código SIMPRAS',
            'produto.codigo_barras' => 'Código de Barras',
            'produto.grupo_produto_id' => 'Grupo',
            'produto.unidade_medida_id' => 'Unidade de Medida',
            'produto.status' => 'Status'
        ];
    }
}
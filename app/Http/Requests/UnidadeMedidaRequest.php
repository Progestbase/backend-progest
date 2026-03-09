<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class UnidadeMedidaRequest extends BaseFormRequest
{

    public function rules()
    {
        // Pega o ID da requisição, caso seja uma edição (update)
        $id = $this->input('unidadeMedida.id');
        $isUpdate = !empty($id);

        return [
            'unidadeMedida.id' => $isUpdate ? 'required|integer|exists:unidade_medida,id' : 'nullable',
            // 'unique' verifica a tabela 'unidade_medida', coluna 'nome', e ignora o registro atual na edição
            'unidadeMedida.nome' => 'required|string|max:100|unique:unidade_medida,nome,' . $id,
            'unidadeMedida.quantidade_unidade_minima' => 'required|integer|min:1',
            'unidadeMedida.status' => 'required|in:A,I' // Valida se é A ou I
        ];
    }

    // Opcional: Personalizar nomes dos atributos para a mensagem ficar bonita
    public function attributes()
    {
        return [
            'unidadeMedida.nome' => 'Nome',
            'unidadeMedida.quantidade_unidade_minima' => 'Qtd. Mínima',
            'unidadeMedida.status' => 'Status',
        ];
    }

    // Mensagens personalizadas (Opcional)
    public function messages()
    {
        return [
            'unidadeMedida.nome.required' => 'O nome da unidade é obrigatório.',
            'unidadeMedida.quantidade_unidade_minima.required' => 'A quantidade de unidades mínima é obrigatório.',   
            'unidadeMedida.status.required' => 'O status da unidade é obrigatório.',

            'unidadeMedida.nome.max' => 'O tamanho máximo do nome é de 100 caracteres.',
            'unidadeMedida.nome.unique' => 'Já existe uma unidade de medida com este nome.',
            'unidadeMedida.quantidade_unidade_minima.min' => 'A quantidade mínima deve ser pelo menos 1.',
        ];
    }
}
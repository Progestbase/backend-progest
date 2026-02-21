<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class UnidadeMedidaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Pega o ID da requisição, caso seja uma edição (update)
        $id = $this->input('unidadeMedida.id');

        return [
            // 'unique' verifica a tabela 'unidade_medida', coluna 'nome', e ignora o registro atual na edição
            'unidadeMedida.nome' => 'required|string|max:255|unique:unidade_medida,nome,' . $id,
            'unidadeMedida.quantidade_unidade_minima' => 'required|integer|min:1',
            'unidadeMedida.status' => 'nullable|in:A,I' // Opcional, valida se é A ou I
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'validacao' => true,
            'erros' => $validator->errors()
        ], 200)); // Forçamos 200 para cair no "if" do frontend
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
            'unidadeMedida.nome.unique' => 'Já existe uma unidade de medida com este nome.',
            'unidadeMedida.quantidade_unidade_minima.min' => 'A quantidade mínima deve ser pelo menos 1.',
        ];
    }
}

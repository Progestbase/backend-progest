<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class StoreUnidadeMedidaRequest extends FormRequest
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
        return [
            'unidadeMedida.nome' => 'required|string|max:255',
            'unidadeMedida.quantidade_unidade_minima' => 'required|integer|min:1',
            'unidadeMedida.status' => 'nullable|in:A,I' // Opcional, valida se é A ou I
        ];
    }

    // --- ADICIONE ESTE MÉTODO ---
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'validacao' => true,
            'erros' => $validator->errors()
        ], 200)); // Forçamos 200 para cair no "if" do seu frontend
    }

    // Opcional: Personalizar nomes dos atributos para a mensagem ficar bonita
    public function attributes()
    {
        return [
            'unidadeMedida.nome' => 'Nome',
            'unidadeMedida.quantidade_unidade_minima' => 'Qtd. Mínima',
        ];
    }

    // Mensagens personalizadas (Opcional)
    public function messages()
    {
        return [
            'unidadeMedida.nome.required' => 'O nome da unidade é obrigatório.',
            'unidadeMedida.quantidade_unidade_minima.min' => 'A quantidade mínima deve ser pelo menos 1.',
        ];
    }
}

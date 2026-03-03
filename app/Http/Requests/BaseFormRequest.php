<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Autoriza todos por padrão (a lógica de permissão ACL virá em outro lugar/middleware)
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Padroniza o erro para o formato que seu Frontend Vue.js espera (Status 200 + JSON)
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'validacao' => true,
            'erros' => $validator->errors()
        ], status: 422));
    }
}
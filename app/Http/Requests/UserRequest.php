<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize()
    {
        return true; 
    }

    public function rules()
    {
        $id = $this->input('user.id');
        $isUpdate = !empty($id);

        return [
            'user.name' => 'required|string|max:255',
            'user.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id)
            ],
            'user.cpf' => [
                'required',
                'string',
                'max:14',
                Rule::unique('users', 'cpf')->ignore($id)
            ],
            'user.data_nascimento' => 'required|date|before:-14 years',
            'user.telefone' => 'nullable|string',
            'user.tipo_vinculo' => 'required|exists:tipo_vinculo,id',
            
            'user.password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                'min:8',
                'regex:/[a-z]/',     // minúscula
                'regex:/[A-Z]/',     // maiúscula
                'regex:/[0-9]/'     // número
            ],
            'user.status' => 'nullable|in:A,I'
        ];
    }

    public function messages()
    {
        return [
            'user.email.unique' => 'Este e-mail já está em utilização.',
            'user.cpf.unique' => 'Este CPF já se encontra registado.',
            'user.data_nascimento.before' => 'O utilizador deve ter pelo menos 14 anos de idade.',
            'user.password.regex' => 'A palavra-passe deve conter pelo menos um letra maiúscula, uma minúscula e um número.',
            'user.tipo_vinculo.required' => 'O tipo de vínculo é obrigatório.'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'validacao' => true,
            'erros' => $validator->errors()
        ], 422));
    }
}
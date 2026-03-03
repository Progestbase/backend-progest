<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends BaseFormRequest
{

    public function rules()
    {
        $id = $this->input('user.id');
        $isUpdate = !empty($id);

        return [
            'user.name' => 'required|string|min:3|max:191',
            'user.email' => [
                'required', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($id)
            ],
            'user.cpf' => [
                'required', 'string', 'min:11', 'max:14',
                Rule::unique('users', 'cpf')->ignore($id)
            ],
            'user.data_nascimento' => 'required|date|before:-14 years',
            'user.telefone' => 'nullable|string|max:15',
            'user.tipo_vinculo' => 'required|exists:tipo_vinculo,id',
            'user.status' => 'required|in:A,I',

            'user.password' => [
                $isUpdate ? 'nullable' : 'required',
                'string', 'min:8',
                'regex:/[a-z]/',     // minúscula
                'regex:/[A-Z]/',     // maiúscula
                'regex:/[0-9]/'     // número
            ]
        ];
    }

    public function messages()
    {
        return [
            'user.name.required' => 'O nome do usuário é obrigatório.',
            'user.email.required' => 'O email do usuário é obrigatório.',
            'user.cpf.required' => 'O CPF do usuário é obrigatório.',
            'user.data_nascimento.required' => 'A data de nascimento do usuário é obrigatória.',
            'user.tipo_vinculo.required' => 'O tipo de vínculo do usuário é obrigatório.',
            'user.status.required' => 'O status do usuário é obrigatório.',
            'user.password.required' => 'A palavra-passe é obrigatória.',

            'user.name.min' => 'O nome do usuário deve ter no mínimo 3 caracteres.',
            'user.name.max' => 'O nome do usuário deve ter no máximo 191 caracteres.',
            'user.email.max' => 'O email deve ter no máximo 191 caracteres.',
            'user.cpf.min' => 'O cpf deve ter 11 digitos.',
            'user.cpf.max' => 'O cpf deve ter 11 digitos.',
            'user.telefone.max' => 'O número deve ter no máximo 15 digitos.',
            'user.password.min' => 'A palavra-passe deve conter pelo menos 8 caracteres.',

            'user.email.email' => 'O formato do email está incorreto.',
            'user.data_nascimento.date' => 'O formato da data de nascimento está incorreta.',
            'user.data_nascimento.before' => 'O utilizador deve ter pelo menos 14 anos de idade.',
            'user.password.regex' => 'A palavra-passe deve conter pelo menos uma letra maiúscula, uma minúscula e um número.',
            'user.tipo_vinculo.exists' => 'O tipo de vínculo do não existe.',

            'user.email.unique' => 'Este e-mail já está em utilização.',
            'user.cpf.unique' => 'Este CPF já se encontra registado.',
        ];
    }

    public function attributes()
    {
        return [
            'user.name' => 'Nome',
            'user.email' => 'Email',
            'user.cpf' => 'CPF',
            'user.data_nascimento' => 'Data de Nascimento',
            'user.telefone' => 'Telefone',
            'user.tipo_vinculo' => 'Tipo de Vínculo',
            'user.status' => 'Status',
            'user.password' => 'Senha'
        ];
    }
}
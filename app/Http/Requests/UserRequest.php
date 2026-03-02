<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use Closure;

class UserRequest extends BaseFormRequest
{

    public function rules()
    {
        $id = $this->input('user.id');
        $isUpdate = !empty($id);

        return [
            // ID é necessário para update (ignorado no add)
            'user.id' => $isUpdate ? 'required|integer|exists:users,id' : 'nullable',

            'user.name' => 'required|string|min:3|max:191',
            'user.email' => [
                'required', 'email', 'max:191',
                Rule::unique('users', 'email')->ignore($id)
            ],
            'user.cpf' => [
                'required', 'string', 'min:11', 'max:14',
                Rule::unique('users', 'cpf')->ignore($id),
                function (string $attribute, mixed $value, Closure $fail) {
                    if (!$this->validarCpf($value)) {
                        $fail('O CPF informado não é válido.');
                    }
                },
            ],
            'user.data_nascimento' => 'required|date|before:-14 years',
            'user.telefone' => 'nullable|string|max:20',
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

    /**
     * Valida CPF usando o algoritmo oficial dos dígitos verificadores.
     * Aceita com ou sem máscara (ex: "123.456.789-09" ou "12345678909").
     */
    private function validarCpf(string $cpf): bool
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/\D/', '', $cpf);

        // Deve ter exatamente 11 dígitos
        if (strlen($cpf) !== 11) {
            return false;
        }

        // Rejeitar CPFs com todos os dígitos iguais (ex: 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Cálculo do primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += intval($cpf[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : (11 - $resto);

        if (intval($cpf[9]) !== $digito1) {
            return false;
        }

        // Cálculo do segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += intval($cpf[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : (11 - $resto);

        if (intval($cpf[10]) !== $digito2) {
            return false;
        }

        return true;
    }

    public function messages()
    {
        return [
            'user.id.required' => 'O ID do usuário é obrigatório para atualização.',
            'user.id.exists' => 'Usuário não encontrado.',
            'user.name.required' => 'O nome do usuário é obrigatório.',
            'user.email.required' => 'O email do usuário é obrigatório.',
            'user.cpf.required' => 'O CPF do usuário é obrigatório.',
            'user.data_nascimento.required' => 'A data de nascimento do usuário é obrigatória.',
            'user.tipo_vinculo.required' => 'O tipo de vínculo do usuário é obrigatório.',
            'user.status.required' => 'O status do usuário é obrigatório.',
            'user.password.required' => 'A senha é obrigatória.',

            'user.name.min' => 'O nome do usuário deve ter no mínimo 3 caracteres.',
            'user.name.max' => 'O nome do usuário deve ter no máximo 191 caracteres.',
            'user.email.max' => 'O email deve ter no máximo 191 caracteres.',
            'user.cpf.min' => 'O CPF deve ter 11 dígitos.',
            'user.cpf.max' => 'O CPF deve ter no máximo 14 caracteres.',
            'user.telefone.max' => 'O telefone deve ter no máximo 20 caracteres.',
            'user.password.min' => 'A senha deve conter pelo menos 8 caracteres.',

            'user.email.email' => 'O formato do email está incorreto.',
            'user.data_nascimento.date' => 'O formato da data de nascimento está incorreto.',
            'user.data_nascimento.before' => 'O usuário deve ter pelo menos 14 anos de idade.',
            'user.password.regex' => 'A senha deve conter pelo menos uma letra maiúscula, uma minúscula e um número.',
            'user.tipo_vinculo.exists' => 'O tipo de vínculo selecionado não existe.',

            'user.email.unique' => 'Este e-mail já está em uso.',
            'user.cpf.unique' => 'Este CPF já está registrado.',
        ];
    }

    public function attributes()
    {
        return [
            'user.id' => 'ID',
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
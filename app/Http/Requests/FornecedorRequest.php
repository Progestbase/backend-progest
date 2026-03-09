<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class FornecedorRequest extends BaseFormRequest
{
    public function rules()
    {
        $data = $this->input('fornecedor', $this->all());
        $id = $data['id'] ?? null;
        $isUpdate = !empty($id);

        $rules = [
            'fornecedor.id' => $isUpdate ? 'required|integer|exists:fornecedores,id' : 'nullable',
            'fornecedor.razao_social_nome' => 'required|string|min:3|max:191',
            'fornecedor.tipo_pessoa' => 'required|in:F,J',
            'fornecedor.status' => 'required|in:A,I',
        ];

        // Validação condicional para CPF ou CNPJ
        if (($data['tipo_pessoa'] ?? '') === 'F') {
            $rules['fornecedor.cpf'] = [
                'required',
                'string',
                'min:11',
                'max:14',
                'unique:fornecedores,cpf,' . $id,
                function ($attribute, $value, $fail) {
                    if (!$this->validarCpf($value)) {
                        $fail('O CPF informado é inválido.');
                    }
                },
            ];
            $rules['fornecedor.cnpj'] = 'nullable';
        } else {
            $rules['fornecedor.cnpj'] = [
                'required',
                'string',
                'min:14',
                'max:18',
                'unique:fornecedores,cnpj,' . $id,
                function ($attribute, $value, $fail) {
                    if (!$this->validarCnpj($value)) {
                        $fail('O CNPJ informado é inválido.');
                    }
                },
            ];
            $rules['fornecedor.cpf'] = 'nullable';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'fornecedor.razao_social_nome.required' => 'O "nome/razão social" do fornecedor é obrigatório.',
            'fornecedor.cnpj.required' => 'O CNPJ do fornecedor é obrigatório.',
            'fornecedor.cpf.required' => 'O CPF do fornecedor é obrigatório.',
            'fornecedor.tipo_pessoa.required' => 'O tipo de pessoa do fornecedor é obrigatória.',
            'fornecedor.status.required' => 'O status do fornecedor é obrigatório.',

            'fornecedor.razao_social_nome.min' => 'O "nome/razão social" deve ter no mínimo 3 caracteres.',
            'fornecedor.razao_social_nome.max' => 'O "nome/razão social" deve ter no máximo 191 caracteres.',
            'fornecedor.cnpj.min' => 'O CNPJ deve ter exatamente 14 dígitos.',
            'fornecedor.cnpj.max' => 'O CNPJ deve ter exatamente 14 dígitos.',
            'fornecedor.cpf.min' => 'O CPF deve ter exatamente 11 dígitos.',
            'fornecedor.cpf.max' => 'O CPF deve ter exatamente 11 dígitos.',

            'fornecedor.tipo_pessoa.in' => 'Tipo de pessoa deve ser F (Física) ou J (Jurídica)',
            'fornecedor.status.in' => 'Status deve ser A (Ativo) ou I (Inativo)',
            'fornecedor.cnpj.unique' => 'Este CNPJ já está cadastrado.',
            'fornecedor.cpf.unique' => 'Este CPF já está cadastrado.'
        ];
    }

    public function attributes()
    {
        return [
            'fornecedor.razao_social_nome' => '"Nome/Razão Social"',
            'fornecedor.cnpj' => 'CNPJ',
            'fornecedor.cpf' => 'CPF',
            'fornecedor.tipo_pessoa' => 'Tipo de Pessoa',
            'fornecedor.status' => 'Status'
        ];
    }

    /**
     * Valida CPF usando o algoritmo oficial dos dígitos verificadores.
     */
    private function validarCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf);

        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += intval($cpf[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : (11 - $resto);
        if (intval($cpf[9]) !== $digito1) return false;

        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += intval($cpf[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : (11 - $resto);
        if (intval($cpf[10]) !== $digito2) return false;

        return true;
    }

    /**
     * Valida CNPJ usando o algoritmo oficial dos dígitos verificadores.
     */
    private function validarCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        // Primeiro dígito verificador
        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += intval($cnpj[$i]) * $pesos1[$i];
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : (11 - $resto);
        if (intval($cnpj[12]) !== $digito1) return false;

        // Segundo dígito verificador
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += intval($cnpj[$i]) * $pesos2[$i];
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : (11 - $resto);
        if (intval($cnpj[13]) !== $digito2) return false;

        return true;
    }
}

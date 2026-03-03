<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class FornecedorRequest extends BaseFormRequest
{
    public function rules()
    {
        $data = $this->input('fornecedor', $this->all());
        $id = $data['id'] ?? null;

        $rules = [
            'fornecedor.razao_social_nome' => 'required|string|min:3|max:191',
            'fornecedor.tipo_pessoa' => 'required|in:F,J',
            'fornecedor.status' => 'required|in:A,I',
        ];

        // Validação condicional para CPF ou CNPJ
        if (($data['tipo_pessoa'] ?? '') === 'F') {
            $rules['fornecedor.cpf'] = 'required|string|min:11|max:14|unique:fornecedores,cpf,' . $id;
            $rules['fornecedor.cnpj'] = 'nullable';
        } else {
            $rules['fornecedor.cnpj'] = 'required|string|min:14|max:18|unique:fornecedores,cnpj,' . $id;
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

            'fornecedor.razao_social_nome.min' => 'O "nome/razão social" do usuário deve ter no mínimo 3 caracteres.',
            'fornecedor.razao_social_nome.max' => 'O "nome/razão social" do usuário deve ter no máximo 191 caracteres.',
            'fornecedor.cnpj.min' => 'O cnpj deve ter exatamente 14 digitos.',
            'fornecedor.cnpj.max' => 'O cnpj deve ter exatamente 14 digitos.',
            'fornecedor.cpf.min' => 'O cpf deve ter exatamente 11 digitos.',
            'fornecedor.cpf.max' => 'O cpf deve ter exatamente 11 digitos.',
            
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
}

            

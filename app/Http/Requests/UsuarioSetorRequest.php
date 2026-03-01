<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;

class UsuarioSetorRequest extends BaseFormRequest
{
    public function rules()
    {
        return [
            'usuario_id' => 'required|integer|exists:users,id',
            'setor_id' => 'required|integer|exists:setores,id',
            // O perfil é opcional no "delete", mas obrigatório no "create/update"
            'perfil' => 'nullable|string|in:admin,almoxarife,solicitante', 
        ];
    }

    public function messages()
    {
        return [
            'usuario_id.required' => 'O usuário é obrigatório.',
            'usuario_id.exists' => 'O usuário selecionado não existe no sistema.',

            'setor_id.required' => 'O setor é obrigatório.',
            'setor_id.exists' => 'O setor selecionado não existe no sistema.',
            
            'perfil.in' => 'O perfil deve ser admin, almoxarife ou solicitante.',
        ];
    }
}
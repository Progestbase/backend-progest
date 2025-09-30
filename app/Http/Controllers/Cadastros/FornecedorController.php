<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FornecedorController
{
    /**
     * Listar todos os fornecedores
     */
    public function listAll(Request $request)
    {
        try {
            $data = $request->all();
            $filters = $data['filters'] ?? [];
            $perPage = $data['per_page'] ?? 15;

            $query = Fornecedor::query();

            // Aplicar filtros
            foreach ($filters as $condition) {
                foreach ($condition as $column => $value) {
                    if ($value !== null && $value !== '') {
                        $query->where($column, 'like', '%' . $value . '%');
                    }
                }
            }

            $fornecedores = $query
                ->select('id', 'tipo_pessoa', 'razao_social_nome', 'cpf', 'cnpj', 'status', 'created_at', 'updated_at')
                ->orderBy('razao_social_nome')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'data' => $fornecedores
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar fornecedores: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obter dados de um fornecedor específico
     */
    public function listData(Request $request)
    {
        try {
            $data = $request->all();
            $id = $data['id'];

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do fornecedor é obrigatório'
                ], 400);
            }

            $fornecedor = Fornecedor::find($id);

            if (!$fornecedor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fornecedor não encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $fornecedor
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar fornecedor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Criar novo fornecedor
     */
    public function add(Request $request)
    {
        try {
            $data = $request->all();
            $fornecedorData = $data['fornecedor'] ?? $data;

            // Validações personalizadas
            $rules = [
                'tipo_pessoa' => 'required|in:F,J',
                'razao_social_nome' => 'required|string|max:255',
                'status' => 'in:A,I'
            ];

            // Validação condicional para CPF ou CNPJ
            if ($fornecedorData['tipo_pessoa'] === 'F') {
                $rules['cpf'] = 'required|string|size:11|unique:fornecedores,cpf';
                $rules['cnpj'] = 'nullable';
            } else {
                $rules['cnpj'] = 'required|string|size:14|unique:fornecedores,cnpj';
                $rules['cpf'] = 'nullable';
            }

            $validator = Validator::make($fornecedorData, $rules, [
                'tipo_pessoa.required' => 'O tipo de pessoa é obrigatório',
                'tipo_pessoa.in' => 'Tipo de pessoa deve ser F (Física) ou J (Jurídica)',
                'razao_social_nome.required' => 'A razão social/nome é obrigatória',
                'cpf.required' => 'CPF é obrigatório para pessoa física',
                'cpf.size' => 'CPF deve ter exatamente 11 dígitos',
                'cpf.unique' => 'Este CPF já está cadastrado',
                'cnpj.required' => 'CNPJ é obrigatório para pessoa jurídica',
                'cnpj.size' => 'CNPJ deve ter exatamente 14 dígitos',
                'cnpj.unique' => 'Este CNPJ já está cadastrado'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            // Criar fornecedor
            $fornecedor = new Fornecedor();
            $fornecedor->tipo_pessoa = $fornecedorData['tipo_pessoa'];
            $fornecedor->razao_social_nome = mb_strtoupper(trim($fornecedorData['razao_social_nome']));
            $fornecedor->cpf = $fornecedorData['tipo_pessoa'] === 'F' ? $fornecedorData['cpf'] : null;
            $fornecedor->cnpj = $fornecedorData['tipo_pessoa'] === 'J' ? $fornecedorData['cnpj'] : null;
            $fornecedor->status = $fornecedorData['status'] ?? 'A';

            $fornecedor->save();

            return response()->json([
                'status' => true,
                'data' => $fornecedor,
                'message' => 'Fornecedor cadastrado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar fornecedor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Atualizar fornecedor existente
     */
    public function update(Request $request)
    {
        try {
            $data = $request->all();
            $fornecedorData = $data['fornecedor'] ?? $data;
            $id = $fornecedorData['id'] ?? null;

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do fornecedor é obrigatório'
                ], 400);
            }

            // Verificar se o fornecedor existe
            $fornecedor = Fornecedor::find($id);
            if (!$fornecedor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fornecedor não encontrado'
                ], 404);
            }

            // Validações
            $rules = [
                'tipo_pessoa' => 'required|in:F,J',
                'razao_social_nome' => 'required|string|max:255',
                'status' => 'in:A,I'
            ];

            // Validação condicional para CPF ou CNPJ
            if ($fornecedorData['tipo_pessoa'] === 'F') {
                $rules['cpf'] = 'required|string|size:11|unique:fornecedores,cpf,' . $id;
                $rules['cnpj'] = 'nullable';
            } else {
                $rules['cnpj'] = 'required|string|size:14|unique:fornecedores,cnpj,' . $id;
                $rules['cpf'] = 'nullable';
            }

            $validator = Validator::make($fornecedorData, $rules, [
                'tipo_pessoa.required' => 'O tipo de pessoa é obrigatório',
                'tipo_pessoa.in' => 'Tipo de pessoa deve ser F (Física) ou J (Jurídica)',
                'razao_social_nome.required' => 'A razão social/nome é obrigatória',
                'cpf.required' => 'CPF é obrigatório para pessoa física',
                'cpf.size' => 'CPF deve ter exatamente 11 dígitos',
                'cpf.unique' => 'Este CPF já está cadastrado',
                'cnpj.required' => 'CNPJ é obrigatório para pessoa jurídica',
                'cnpj.size' => 'CNPJ deve ter exatamente 14 dígitos',
                'cnpj.unique' => 'Este CNPJ já está cadastrado'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            // Atualizar fornecedor
            $fornecedor->tipo_pessoa = $fornecedorData['tipo_pessoa'];
            $fornecedor->razao_social_nome = mb_strtoupper(trim($fornecedorData['razao_social_nome']));
            $fornecedor->cpf = $fornecedorData['tipo_pessoa'] === 'F' ? $fornecedorData['cpf'] : null;
            $fornecedor->cnpj = $fornecedorData['tipo_pessoa'] === 'J' ? $fornecedorData['cnpj'] : null;
            $fornecedor->status = $fornecedorData['status'] ?? 'A';

            $fornecedor->save();

            return response()->json([
                'status' => true,
                'data' => $fornecedor,
                'message' => 'Fornecedor atualizado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar fornecedor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Excluir fornecedor
     */
    public function delete(Request $request)
    {
        try {
            $data = $request->all();
            $id = $data['id'] ?? null;

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do fornecedor é obrigatório'
                ], 400);
            }

            $fornecedor = Fornecedor::find($id);
            if (!$fornecedor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fornecedor não encontrado'
                ], 404);
            }

            // Verificar se há dependências antes de excluir
            // Aqui você pode adicionar verificações de relacionamentos se necessário
            // Exemplo: if ($fornecedor->entradas()->count() > 0) { ... }

            $fornecedor->delete();

            return response()->json([
                'status' => true,
                'message' => 'Fornecedor excluído com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir fornecedor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Alterar status do fornecedor (ativar/inativar)
     */
    public function toggleStatus(Request $request)
    {
        try {
            $data = $request->all();
            $id = $data['id'] ?? null;

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do fornecedor é obrigatório'
                ], 400);
            }

            $fornecedor = Fornecedor::find($id);
            if (!$fornecedor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fornecedor não encontrado'
                ], 404);
            }

            $fornecedor->status = $fornecedor->status === 'A' ? 'I' : 'A';
            $fornecedor->save();

            $statusText = $fornecedor->status === 'A' ? 'ativado' : 'inativado';

            return response()->json([
                'status' => true,
                'data' => $fornecedor,
                'message' => "Fornecedor {$statusText} com sucesso"
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status do fornecedor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}

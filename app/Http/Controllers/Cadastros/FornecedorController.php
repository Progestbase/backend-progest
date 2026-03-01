<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Requests\FornecedorRequest;
use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FornecedorController
{
    /**
     * Criar novo fornecedor
     */
    public function add(FornecedorRequest $request)
    {
        try {
            $data = $request->validated()['fornecedor'] ?? $request->all()['fornecedor'];

            // Criar fornecedor
            $fornecedor = new Fornecedor();
            $fornecedor->tipo_pessoa = $data['tipo_pessoa'];
            $fornecedor->razao_social_nome = trim($data['razao_social_nome']);
            $fornecedor->cpf = $data['tipo_pessoa'] === 'F' ? $data['cpf'] : null;
            $fornecedor->cnpj = $data['tipo_pessoa'] === 'J' ? $data['cnpj'] : null;
            $fornecedor->status = $data['status'] ?? 'A';

            $fornecedor->save();

            return response()->json([
                'status' => true,
                'data' => $fornecedor,
                'message' => 'Fornecedor cadastrado com sucesso'
            ], 201);
        } catch (\Throwable $e) { // Trocamos \Exception por \Throwable para capturar até erros do PHP 8
            Log::error('Erro ao salvar fornecedor: ' . $e->getMessage());
            
            // Vai mandar o erro real do Banco de Dados direto para o alerta do seu Vue.js!
            return response()->json([
                'status' => false,
                'message' => 'Erro Técnico: ' . $e->getMessage() . ' (Linha ' . $e->getLine() . ')'
            ], 500);
        }
    }

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
     * Atualizar fornecedor existente
     */
    public function update(FornecedorRequest $request)
    {
        try {
            $data = $request->validated()['fornecedor'] ?? $request->all()['fornecedor'];
            $id = $data['id'] ?? null;

            // Verificar se o fornecedor existe
            $fornecedor = Fornecedor::find($id);
            if (!$fornecedor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Fornecedor não encontrado'
                ], 404);
            }

            // Atualizar fornecedor
            $fornecedor->tipo_pessoa = $data['tipo_pessoa'];
            $fornecedor->razao_social_nome = trim($data['razao_social_nome']);
            $fornecedor->cpf = $data['tipo_pessoa'] === 'F' ? $data['cpf'] : null;
            $fornecedor->cnpj = $data['tipo_pessoa'] === 'J' ? $data['cnpj'] : null;
            $fornecedor->status = $data['status'] ?? $fornecedor->status;
            $fornecedor->save();

            return response()->json([
                'status' => true,
                'data' => $fornecedor,
                'message' => 'Fornecedor atualizado com sucesso'
            ]);
        } catch (\Throwable $e) { // Trocamos \Exception por \Throwable para capturar até erros do PHP 8
            Log::error('Erro ao salvar fornecedor: ' . $e->getMessage());
            
            // Vai mandar o erro real do Banco de Dados direto para o alerta do seu Vue.js!
            return response()->json([
                'status' => false,
                'message' => 'Erro Técnico: ' . $e->getMessage() . ' (Linha ' . $e->getLine() . ')'
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

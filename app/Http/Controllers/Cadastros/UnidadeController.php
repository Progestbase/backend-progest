<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UnidadeController
{
    /**
     * Listar todas as unidades
     */
    public function listAll(Request $request)
    {
        try {
            $data = $request->all();
            $filters = $data['filters'] ?? [];
            $perPage = $data['per_page'] ?? 15;

            $query = Unidade::query();

            // Aplicar filtros
            foreach ($filters as $condition) {
                foreach ($condition as $column => $value) {
                    if ($value !== null && $value !== '') {
                        if ($column === 'status') {
                            $query->where($column, $value);
                        } else {
                            $query->where($column, 'like', '%' . $value . '%');
                        }
                    }
                }
            }

            $unidades = $query
                ->orderBy('nome')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'data' => $unidades
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar unidades: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obter dados de uma unidade específica
     */
    public function listData(Request $request)
    {
        try {
            $data = $request->all();
            $id = $data['id'];

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID da unidade é obrigatório'
                ], 400);
            }

            $unidade = Unidade::find($id);

            if (!$unidade) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unidade não encontrada'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $unidade
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Criar nova unidade
     */
    public function add(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'nome' => 'required|string|max:255',
                'status' => 'sometimes|in:A,I'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors()
                ], 400);
            }

            $unidade = Unidade::create([
                'nome' => $data['nome'],
                'status' => $data['status'] ?? 'A'
            ]);

            return response()->json([
                'status' => true,
                'data' => $unidade,
                'message' => 'Unidade criada com sucesso'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Atualizar unidade existente
     */
    public function update(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'id' => 'required|exists:unidades,id',
                'nome' => 'required|string|max:255',
                'status' => 'sometimes|in:A,I'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors()
                ], 400);
            }

            $unidade = Unidade::find($data['id']);

            if (!$unidade) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unidade não encontrada'
                ], 404);
            }

            $unidade->update([
                'nome' => $data['nome'],
                'status' => $data['status'] ?? $unidade->status
            ]);

            return response()->json([
                'status' => true,
                'data' => $unidade,
                'message' => 'Unidade atualizada com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Deletar unidade (soft delete via status)
     */
    public function delete(Request $request, $id)
    {
        try {
            $unidade = Unidade::find($id);

            if (!$unidade) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unidade não encontrada'
                ], 404);
            }

            // Verificar se há setores vinculados
            if ($unidade->setores()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Não é possível deletar unidade com setores vinculados'
                ], 400);
            }

            $unidade->delete();

            return response()->json([
                'status' => true,
                'message' => 'Unidade deletada com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Alternar status da unidade (A/I)
     */
    public function toggleStatus(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID da unidade é obrigatório'
                ], 400);
            }

            $unidade = Unidade::find($data['id']);

            if (!$unidade) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unidade não encontrada'
                ], 404);
            }

            $unidade->status = $unidade->status === 'A' ? 'I' : 'A';
            $unidade->save();

            return response()->json([
                'status' => true,
                'data' => $unidade,
                'message' => 'Status alterado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao alternar status da unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}

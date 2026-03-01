<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\Unidade;
use Illuminate\Http\Request;
use App\Http\Requests\UnidadeRequest;
use Illuminate\Support\Facades\Log;

class UnidadeController
{
    /**
     * Criar nova unidade
     */
    public function add(UnidadeRequest $request)
    {
        try {
            $data = $request->validated();

            $unidade = Unidade::create([
                'nome' => mb_strtoupper($data['nome']),
                'status' => $data['status'] ?? 'A'
            ]);

            return response()->json([
                'status' => true,
                'data' => $unidade,
                'message' => 'Unidade criada com sucesso'
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno ao salvar unidade: ' . $e->getMessage()
            ], 500);
        }
    }

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
        } catch (\Throwable $e) {
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
            $id = $request->input('id');

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
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Atualizar unidade existente
     */
    public function update(UnidadeRequest $request)
    {
        try {
            $data = $request->validated();

            $unidade = Unidade::find($data['id']);

            if (!$unidade) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unidade não encontrada'
                ], 404);
            }

            $unidade->update([
                'nome' => mb_strtoupper($data['nome']),
                'status' => $data['status'] ?? $unidade->status
            ]);

            return response()->json([
                'status' => true,
                'data' => $unidade,
                'message' => 'Unidade atualizada com sucesso'
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno ao atualizar unidade: ' . $e->getMessage()
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

            // Verificar se há setores vinculados e retornar 422 para o Interceptor
            $setoresCount = $unidade->setores()->count();
            if ($setoresCount > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Não é possível deletar esta unidade pois possui setores vinculados.',
                    'references' => ['setores (' . $setoresCount . ')']
                ], 422);
            }

            $unidade->delete();

            return response()->json([
                'status' => true,
                'message' => 'Unidade deletada com sucesso'
            ]);
        } catch (\Throwable $e) {
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
            $id = $request->input('id');

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

            $unidade->status = $unidade->status === 'A' ? 'I' : 'A';
            $unidade->save();

            return response()->json([
                'status' => true,
                'data' => $unidade,
                'message' => 'Status alterado com sucesso'
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao alternar status da unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}
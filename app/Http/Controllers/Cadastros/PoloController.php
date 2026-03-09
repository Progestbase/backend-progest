<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Requests\PoloRequest;
use App\Models\Polo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PoloController
{
    public function add(PoloRequest $request)
    {
        try {
            $data = $request->validated();

            $polo = Polo::create([
                'nome' => $data['nome'],
                'status' => $data['status'] ?? 'A'
            ]);

            return response()->json(['status' => true, 'data' => $polo, 'message' => 'Polo criado com sucesso'], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar polo: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }
    
    public function listAll(Request $request)
    {
        try {
            $query = Polo::query();

            // Busca textual por nome
            $search = $request->input('search');
            if (!empty($search)) {
                $query->where('nome', 'LIKE', '%' . $search . '%');
            }

            // Filtros legados (compatibilidade)
            $filters = $request->input('filters', []);
            foreach ($filters as $condition) {
                if (is_array($condition)) {
                    foreach ($condition as $column => $value) {
                        if ($value !== null && $value !== '') {
                            $allowedColumns = ['nome', 'status'];
                            if (in_array($column, $allowedColumns)) {
                                if ($column === 'status') {
                                    $query->where($column, $value);
                                } else {
                                    $query->where($column, 'LIKE', '%' . $value . '%');
                                }
                            }
                        }
                    }
                }
            }

            // Ordenação dinâmica
            $sortBy = $request->input('sort_by', 'nome');
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSortColumns = ['id', 'nome', 'status'];
            if (in_array($sortBy, $allowedSortColumns) && in_array(strtolower($sortDir), ['asc', 'desc'])) {
                $query->orderBy($sortBy, $sortDir);
            } else {
                $query->orderBy('nome', 'asc');
            }

            $polos = $query->select('id', 'nome', 'status')->get();

            return response()->json(['status' => true, 'data' => $polos]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar polos: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    public function listData(Request $request)
    {
        try {
            $id = $request->input('id');

            if (!$id) return response()->json(['status' => false, 'message' => 'ID é obrigatório'], 400);

            $polo = Polo::find($id);

            if (!$polo) return response()->json(['status' => false, 'message' => 'Polo não encontrado'], 404);

            return response()->json(['status' => true, 'data' => $polo]);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar polo: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    public function update(PoloRequest $request)
    {
        try {
            $data = $request->validated();

            $polo = Polo::find($data['id']);

            if (!$polo) return response()->json(['status' => false, 'message' => 'Polo não encontrado'], 404);

            $polo->update([
                'nome' => $data['nome'],
                'status' => $data['status'] ?? $polo->status
            ]);

            return response()->json(['status' => true, 'data' => $polo, 'message' => 'Polo atualizado com sucesso']);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar polo: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $polo = Polo::find($id);

            if (!$polo) return response()->json(['status' => false, 'message' => 'Unidade não encontrada'], 404);

            // Toggle: se ativo → inativa, se inativo → ativa
            if ($polo->status === 'A') {
                // Ao inativar, verificar referências
                $setoresCount = $polo->setores()->count();
                if ($setoresCount > 0) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Não é possível inativar: existem ' . $setoresCount . ' setor(es) vinculado(s).',
                    ], 422);
                }
            }

            $polo->status = $polo->status === 'A' ? 'I' : 'A';
            $polo->save();

            $action = $polo->status === 'A' ? 'ativada' : 'inativada';
            return response()->json(['status' => true, 'message' => "Unidade {$action} com sucesso.", 'data' => $polo]);
        } catch (\Throwable $e) {
            Log::error('Erro ao alterar status do polo: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    public function toggleStatus(Request $request)
    {
        try {
            $id = $request->input('id');

            if (!$id) return response()->json(['status' => false, 'message' => 'ID obrigatório'], 400);

            $polo = Polo::find($id);

            if (!$polo) return response()->json(['status' => false, 'message' => 'Polo não encontrado'], 404);

            $polo->status = $polo->status === 'A' ? 'I' : 'A';
            $polo->save();

            return response()->json(['status' => true, 'data' => $polo, 'message' => 'Status alterado com sucesso']);
        } catch (\Throwable $e) {
            Log::error('Erro ao alternar status do polo: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }
}
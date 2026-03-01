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
                'nome' => mb_strtoupper($data['nome']),
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
            $data = $request->all();
            $filters = $data['filters'] ?? [];
            $perPage = $data['per_page'] ?? 15;

            $query = Polo::query();

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

            $polos = $query->orderBy('nome')->paginate($perPage);

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
                'nome' => mb_strtoupper($data['nome']),
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

            if (!$polo) return response()->json(['status' => false, 'message' => 'Polo não encontrado'], 404);

            // Regra de Negócio: Retornar 422 para o Interceptor apanhar se houver dependências
            $setoresCount = $polo->setores()->count();
            if ($setoresCount > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Não é possível excluir esta unidade pois possui setores vinculados.',
                    'references' => ['setores (' . $setoresCount . ')']
                ], 422); 
            }

            $polo->delete();

            return response()->json(['status' => true, 'message' => 'Polo deletado com sucesso']);
        } catch (\Throwable $e) {
            Log::error('Erro ao deletar polo: ' . $e->getMessage());
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
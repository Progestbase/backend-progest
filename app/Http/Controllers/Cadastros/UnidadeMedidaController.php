<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\UnidadeMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UnidadeMedidaRequest;

class UnidadeMedidaController
{
    public function add(UnidadeMedidaRequest $request)
    {
        // O Laravel já validou. Se chegou aqui, os dados estão certos.
        // validated() devolve apenas os campos que passaram pelas regras que você definiu no método rules() do seu FormRequest.
        $dadosValidados = $request->validated(); 
        
        $um = new UnidadeMedida;
        $um->nome = $dadosValidados['unidadeMedida']['nome'];
        $um->quantidade_unidade_minima = $dadosValidados['unidadeMedida']['quantidade_unidade_minima'];
        $um->status = $dadosValidados['unidadeMedida']['status'] ?? 'A';
        $um->save();

        return ['status' => true, 'data' => $um];
    }

    public function update(UnidadeMedidaRequest $request)
    {
        $dadosValidados = $request->validated();

        $um = UnidadeMedida::find($dadosValidados['unidadeMedida']['id']);
        if (!$um) {
            return response()->json(['status' => false, 'message' => 'Unidade de medida não encontrada.'], 404);
        }

        $um->nome = $dadosValidados['unidadeMedida']['nome'];
        $um->quantidade_unidade_minima = $dadosValidados['unidadeMedida']['quantidade_unidade_minima'];
        $um->status = $dadosValidados['unidadeMedida']['status'] ?? $um->status;
        $um->save();

        return ['status' => true, 'data' => $um];
    }

    public function listAll(Request $request)
    {
        $query = UnidadeMedida::query();

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
                    $allowedColumns = ['nome', 'status'];
                    if (in_array($column, $allowedColumns)) {
                        $query->where($column, 'LIKE', '%' . $value . '%');
                    }
                }
            }
        }

        // Ordenação dinâmica
        $sortBy = $request->input('sort_by', 'nome');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSortColumns = ['id', 'nome', 'quantidade_unidade_minima', 'status'];
        if (in_array($sortBy, $allowedSortColumns) && in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('nome', 'asc');
        }

        $result = $query->select('id', 'nome', 'quantidade_unidade_minima', 'status')
            ->get();

        return ['status' => true, 'data' => $result];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $id = $data['id'] ?? null;

        if (!$id) {
            return response()->json(['status' => false, 'message' => 'ID não informado'], 400);
        }

        DB::enableQueryLog();
        $um = UnidadeMedida::find($id);

        return ['status' => true, 'data' => $um, 'query' => DB::getQueryLog()];
    }

    public function delete($id)
    {
        $um = UnidadeMedida::find($id);
        if (!$um) {
            return response()->json(['status' => false, 'message' => 'Unidade de medida não encontrada.'], 404);
        }

        // Toggle: se ativo → inativa, se inativo → ativa
        if ($um->status === 'A') {
            // Ao inativar, verificar referências
            $productCount = DB::table('produtos')->where('unidade_medida_id', $id)->count();
            if ($productCount > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Não é possível inativar: existem ' . $productCount . ' produto(s) vinculado(s) a esta unidade.',
                ], 422);
            }
        }

        $um->status = $um->status === 'A' ? 'I' : 'A';
        $um->save();

        $action = $um->status === 'A' ? 'ativada' : 'inativada';
        return response()->json(['status' => true, 'message' => "Unidade de medida {$action} com sucesso.", 'data' => $um]);
    }
}

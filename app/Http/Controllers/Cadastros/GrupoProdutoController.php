<?php

namespace App\Http\Controllers\Cadastros;

use App\Http\Requests\GrupoProdutoRequest;
use Illuminate\Http\Request;
use App\Models\GrupoProduto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrupoProdutoController
{
    public function add(GrupoProdutoRequest $request)
    {
        try {
            $data = $request->validated();

            $grupoProduto = new GrupoProduto;
            $grupoProduto->nome = $data['grupoProduto']['nome'];
            $grupoProduto->status = $data['grupoProduto']['status'] ?? 'A';
            $grupoProduto->tipo = $data['grupoProduto']['tipo'] ?? 'Material';
            $grupoProduto->save();

            return response()->json(['status' => true, 'data' => $grupoProduto], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao adicionar Grupo de Produto: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno ao salvar. ('.$e->getMessage().')'], 500);
        }
    }

    public function listAll(Request $request)
    {
        $query = GrupoProduto::query();

        // Busca textual por nome
        $search = $request->input('search');
        if (!empty($search)) {
            $query->where('nome', 'LIKE', '%' . $search . '%');
        }

        // Filtro por tipo
        $tipo = $request->input('tipo');
        if (!empty($tipo)) {
            $query->where('tipo', $tipo);
        }

        // Filtros legados (compatibilidade)
        $filters = $request->input('filters', []);
        foreach ($filters as $condition) {
            if (is_array($condition)) {
                foreach ($condition as $column => $value) {
                    $allowedColumns = ['nome', 'status', 'tipo'];
                    if (in_array($column, $allowedColumns)) {
                        $query->where($column, 'LIKE', '%' . $value . '%');
                    }
                }
            }
        }

        // Ordenação dinâmica
        $sortBy = $request->input('sort_by', 'nome');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSortColumns = ['id', 'nome', 'tipo', 'status'];
        if (in_array($sortBy, $allowedSortColumns) && in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('nome', 'asc');
        }

        $grupoProdutos = $query
            ->select('id', 'nome', 'status', 'tipo')
            ->get();

        return response()->json(['status' => true, 'data' => $grupoProdutos]);
    }

    public function listData(Request $request)
    {
        $dataID = $request->input('id');

        DB::enableQueryLog();

        $grupoProduto = GrupoProduto::find($dataID);

        if (!$grupoProduto) {
            return response()->json([
                'status' => false,
                'message' => 'Grupo de produto não encontrado.'
            ], 404);
        }

        return response()->json(['status' => true, 'data' => $grupoProduto]);
    }

    public function update(GrupoProdutoRequest $request)
    {
        try {
            $data = $request->validated();

            $grupoProduto = GrupoProduto::find($data['grupoProduto']['id']);
            if (!$grupoProduto) {
                return response()->json(['status' => false, 'message' => 'Grupo de produto não encontrado.'], 404);
            }

            $grupoProduto->nome = $data['grupoProduto']['nome'];
            $grupoProduto->status = $data['grupoProduto']['status'];
            $grupoProduto->tipo = $data['grupoProduto']['tipo'];
            $grupoProduto->save();

            return response()->json(['status' => true, 'data' => $grupoProduto]);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar Grupo de Produto: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno ao atualizar.'], 500);
        }
    }

    public function delete($id)
    {
        $grupoProduto = GrupoProduto::find($id);

        if (!$grupoProduto) {
            return response()->json([
                'status' => false,
                'message' => 'Grupo de produto não encontrado.'
            ], 404);
        }

        // Toggle: se ativo → inativa, se inativo → ativa
        if ($grupoProduto->status === 'A') {
            // Ao inativar, verificar referências
            $references = $this->checkGrupoProdutoReferences($id);
            if (!empty($references)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Não é possível inativar: existem registros vinculados (' . implode(', ', $references) . ').',
                ], 422);
            }
        }

        $grupoProduto->status = $grupoProduto->status === 'A' ? 'I' : 'A';
        $grupoProduto->save();

        $action = $grupoProduto->status === 'A' ? 'ativado' : 'inativado';
        return response()->json([
            'status' => true,
            'message' => "Grupo de produto {$action} com sucesso.",
            'data' => $grupoProduto
        ]);
    }

    private function checkGrupoProdutoReferences($id)
    {
        $references = [];

        // Verificar produtos relacionados
        $produtoCount = DB::table('produtos')->where('grupo_produto_id', $id)->count();
        if ($produtoCount > 0) {
            $references[] = 'produtos (' . $produtoCount . ')';
        }

        return $references;
    }
}

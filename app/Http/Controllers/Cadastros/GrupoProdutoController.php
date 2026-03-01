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
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $grupoProdutoQuery = GrupoProduto::query();

        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $grupoProdutoQuery->where($column, $value);
            }
        }

        $grupoProdutos = $grupoProdutoQuery
            ->select('id', 'nome', 'status', 'tipo', 'created_at', 'updated_at')
            ->orderBy('nome')
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

        // Verificar se há produtos relacionados
        $references = $this->checkGrupoProdutoReferences($id);
        if (!empty($references)) {
            return response()->json([
                'status' => false,
                'message' => 'Não é possível excluir este grupo de produto pois ele possui registros relacionados no sistema.',
                'references' => $references
            ], 422);
        }

        $grupoProduto->delete();

        return response()->json([
            'status' => true,
            'message' => 'Grupo de produto excluído com sucesso.'
        ], 200);
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

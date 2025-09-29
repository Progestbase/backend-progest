<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use App\Models\GrupoProduto;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GrupoProdutoController
{
    public function add(Request $request) {
        $data = $request->all();

        $validator = Validator::make($data['grupoProduto'], [
            'nome' => 'required|string|max:255',
            'status' => 'required|string|max:1|in:A,I',
            'tipo' => 'required|string|in:Medicamento,Material',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $grupoProduto = new GrupoProduto;
        $grupoProduto->nome = mb_strtoupper($data['grupoProduto']['nome']);
        $grupoProduto->status = $data['grupoProduto']['status'] ?? 'A';
        $grupoProduto->tipo = $data['grupoProduto']['tipo'] ?? 'Material';
        $grupoProduto->save();

        return ['status' => true, 'data' => $grupoProduto];
    }

    public function listAll(Request $request) {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $grupoProdutoQuery = GrupoProduto::query();
        
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $grupoProdutoQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $grupoProdutos = $grupoProdutoQuery
                ->select('id', 'nome', 'status', 'tipo', 'created_at', 'updated_at')
                ->orderBy('nome')
                ->get();
        } else {
            $grupoProdutos = $grupoProdutoQuery
                ->select('id', 'nome', 'status', 'tipo', 'created_at', 'updated_at')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => true, 'data' => $grupoProdutos];
    }

    public function update(Request $request) {
        $data = $request->all();

        $validator = Validator::make($data['grupoProduto'], [
            'id' => 'required|integer|exists:grupo_produto,id',
            'nome' => 'required|string|max:255',
            'status' => 'required|string|max:1|in:A,I',
            'tipo' => 'required|string|in:Medicamento,Material',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $grupoProduto = GrupoProduto::find($data['grupoProduto']['id']);
        if (!$grupoProduto) {
            return response()->json([
                'status' => false,
                'message' => 'Grupo de produto não encontrado.'
            ], 404);
        }

        $grupoProduto->nome = mb_strtoupper($data['grupoProduto']['nome']);
        $grupoProduto->status = $data['grupoProduto']['status'];
        $grupoProduto->tipo = $data['grupoProduto']['tipo'];
        $grupoProduto->save();

        return ['status' => true, 'data' => $grupoProduto];
    }

    public function listData(Request $request) {
        $data = $request->all();
        $dataID = $data['id'];

        DB::enableQueryLog();

        $grupoProduto = GrupoProduto::find($dataID);

        if (!$grupoProduto) {
            return response()->json([
                'status' => false,
                'message' => 'Grupo de produto não encontrado.'
            ], 404);
        }

        return ['status' => true, 'data' => $grupoProduto, 'query' => DB::getQueryLog()];
    }

    public function delete($id) {
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

    private function checkGrupoProdutoReferences($id) {
        $references = [];

        // Verificar produtos relacionados
        $produtoCount = DB::table('produtos')->where('grupo_produto_id', $id)->count();
        if ($produtoCount > 0) {
            $references[] = 'produtos (' . $produtoCount . ')';
        }

        return $references;
    }
}
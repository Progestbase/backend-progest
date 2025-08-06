<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Produtos;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use PhpParser\Builder\Class_;

Class ProdutosController
{
    public function add(Request $request) {
        $data = $request->all();

        $validator = Validator::make($data['produtos'], [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
            'status' => 'required|string|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $produtos = new Produtos;
        $produtos->nome = mb_strtoupper($data['produtos']['nome']);
        $produtos->descricao = $data['produtos']['descricao'] ?? '';
        $produtos->status = $data['produtos']['status'] ?? 'A';
        $produtos->save();

        return ['status' => true, 'data' => $produtos];
    }

    public function listAll(Request $request) {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $produtos = $filters;
        $produtosQuery = Produtos::query();
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $produtosQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $produtos = $produtosQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        } else {
            $produtos = $produtosQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => true, 'data' => $produtos];
    }

    public function update(Request $request) {
        $data = $request->all();

        $validator = Validator::make($data['produtos'], [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
            'status' => 'required|string|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $produtos = Produtos::find($data['produtos']['id']);
        if (!$produtos) {
            return response()->json([
                'status' => false,
                'message' => 'Produto não encontrado.'
            ], 404);
        }

        $produtos->nome = mb_strtoupper($data['produtos']['nome']);
        $produtos->descricao = $data['produtos']['descricao'] ?? '';
        $produtos->status = $data['produtos']['status'] ?? 'A';
        $produtos->save();

        return ['status' => true, 'data' => $produtos];
    }

    public function listData(Request $request) {
        $data = $request->all();
        $dataID = $data['id'];

        DB::enableQueryLog();

        $produtos = Produtos::find($dataID);

        return ['status' => true, 'data' => $produtos, 'query' => DB::getQueryLog()];
    }

    public function delete($id) {
        $produtos = Produtos::find($id);

        if (!$produtos) {
            return response()->json([
                'status' => false,
                'message' => 'Produto não encontrado.'
            ], 404);
        }

        // Verificar usuários
        $references = $this->checkProdutosReferences($id);
        if (!empty($references)) {
            return response()->json([
                'status' => false,
                'message' => 'Não é possível excluir esta unidade pois ela possui registros relacionados no sistema.',
                'references' => $references
            ], 422);
        }
        $produtos->delete();

        return response()->json([
            'status' => true,
            'message' => 'Produto excluído com sucesso.'
        ], 200);
    }

    private function checkProdutosReferences($id) {
        $references = [];

        // Verificar produtos
        $produtoCount = DB::table('produtos')->where('produto_id', $id)->count();
        if ($produtoCount > 0) {
            $references[] = 'produtos (' . $produtoCount . ')';
        }

        return $references;
    }   
}
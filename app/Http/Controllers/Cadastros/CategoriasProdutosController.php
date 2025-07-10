<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Unidades;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use PhpParser\Builder\Class_;
use App\Models\CategoriasProdutos;

Class CategoriasProdutosController
{
    public function add(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data['categoriasProdutos'], [
            'nome' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $categoria = new CategoriasProdutos;
        $categoria->nome = mb_strtoupper($data['categoriasProdutos']['nome']);
        $categoria->descricao = $data['categoriasProdutos']['descricao'] ?? '';
        $status = $data['categoriasProdutos']['status'] ?? 'A';
        if ($status === 'ativo') $status = 'A';
        if ($status === 'inativo') $status = 'I';
        $categoria->status = $status;
        $categoria->save();

        return ['status' => true, 'data' => $categoria];
    }

    public function update(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data['categoriasProdutos'], [
            'id' => 'required|exists:categorias_produtos,id',
            'nome' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $categoria = CategoriasProdutos::find($data['categoriasProdutos']['id']);
        $categoria->nome = mb_strtoupper($data['categoriasProdutos']['nome']);
        $categoria->descricao = $data['categoriasProdutos']['descricao'] ?? '';
        $status = $data['categoriasProdutos']['status'] ?? 'A';
        if ($status === 'ativo') $status = 'A';
        if ($status === 'inativo') $status = 'I';
        $categoria->status = $status;
        $categoria->save();

        return ['status' => true, 'data' => $categoria];
    }

    public function listData(Request $request) {
        $data = $request->all();
        $dataID = $data['id'];
        DB::enableQueryLog();
        $categoria = CategoriasProdutos::find($dataID);
        return ['status' => true, 'data' => $categoria, 'query' => DB::getQueryLog()];
    }

    public function listAll(Request $request) {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $categoriasProdutos = $filters;
        $categoriasProdutosQuery = CategoriasProdutos::query();
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $categoriasProdutosQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $categoriasProdutos = $categoriasProdutosQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        } else {
            $categoriasProdutos = $categoriasProdutosQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => true, 'data' => $categoriasProdutos];
    }
}
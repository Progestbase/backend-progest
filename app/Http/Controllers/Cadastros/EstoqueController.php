<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\Estoque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Unidades;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class EstoqueController
{
    public function add(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['estoque'], [
            'produto_id'       => 'required|string|max:50|unique:estoque,produto_id',
            'unidade_id'         => 'required|string|max:20|unique:estoque,unidade_id',
            'quantidade' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $estoque = new Estoque;
        $estoque->produto_id        = mb_strtoupper($data['estoque']['produto_id']);
        $estoque->unidade_id          = mb_strtoupper($data['estoque']['unidade_id']);
        $estoque->quantidade  = mb_strtoupper($data['estoque']['quantidade']);
        $estoque->status        = $data['estoque']['status'] ?? 'A';

        $estoque->save();

        return ['status' => true, 'data' => $estoque];
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $query = Estoque::query();

        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $query->where($column, $value);
            }
        }

        $fornecedores = $query
            ->select('id', 'produto_id', 'unidade_id', 'quantidade', 'status')
            ->orderBy('produto_id')
            ->get();

        return ['status' => true, 'data' => $fornecedores];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $id = $data['id'];

        DB::enableQueryLog();

        $estoque = Estoque::find($id);

        return ['status' => true, 'data' => $estoque, 'query' => DB::getQueryLog()];
    }

    public function update(Request $request)
    {
        
        $data = $request->all();
        $id = $data['fornecedor']['id'];
        
        // Verifica se o fornecedor existe
        $estoque = Estoque::find($id);

        if (!$estoque) {
            return response()->json([
                'status' => false,
                'message' => 'Fornecedor não encontrado'
            ], 404);
        }   

        $validator = Validator::make($data['fornecedor'], [
            'produto_id'       => 'required|string|max:50|unique:estoque,produto_id,' . $id,
            'unidade_id'         => 'required|string|max:20|unique:estoque,unidade_id,' . $id,
            'quantidade' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $estoque->produto_id        = mb_strtoupper($data['estoque']['produto_id']);
        $estoque->unidade_id          = mb_strtoupper($data['estoque']['unidade_id']);
        $estoque->quantidade  = mb_strtoupper($data['estoque']['quantidade']);
        $estoque->status        = $data['estoque']['status'] ?? 'A';
        $estoque->save();

        return ['status' => true, 'data' => $estoque];
    }
}

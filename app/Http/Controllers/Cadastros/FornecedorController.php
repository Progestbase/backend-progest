<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Unidades;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FornecedorController
{
    public function add(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['fornecedor'], [
            'codigo'       => 'required|string|max:50|unique:fornecedores,codigo',
            'cnpj'         => 'required|string|max:20|unique:fornecedores,cnpj',
            'razao_social' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $fornecedor = new Fornecedor;
        $fornecedor->codigo        = mb_strtoupper($data['fornecedor']['codigo']);
        $fornecedor->cnpj          = mb_strtoupper($data['fornecedor']['cnpj']);
        $fornecedor->razao_social  = mb_strtoupper($data['fornecedor']['razao_social']);
        $fornecedor->status        = $data['fornecedor']['status'] ?? 'A';

        $fornecedor->save();

        return ['status' => true, 'data' => $fornecedor];
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $query = Fornecedor::query();

        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $query->where($column, $value);
            }
        }

        $fornecedores = $query
            ->select('id', 'codigo', 'cnpj', 'razao_social', 'status')
            ->orderBy('razao_social')
            ->get();

        return ['status' => true, 'data' => $fornecedores];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $id = $data['id'];

        DB::enableQueryLog();

        $fornecedor = Fornecedor::find($id);

        return ['status' => true, 'data' => $fornecedor, 'query' => DB::getQueryLog()];
    }

    public function update(Request $request)
    {
        
        $data = $request->all();
        $id = $data['fornecedor']['id'];
        
        // Verifica se o fornecedor existe
        $fornecedor = Fornecedor::find($id);

        if (!$fornecedor) {
            return response()->json([
                'status' => false,
                'message' => 'Fornecedor não encontrado'
            ], 404);
        }   

        $validator = Validator::make($data['fornecedor'], [
            'codigo'       => 'required|string|max:50|unique:fornecedores,codigo,' . $id,
            'cnpj'         => 'required|string|max:20|unique:fornecedores,cnpj,' . $id,
            'razao_social' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $fornecedor->codigo        = mb_strtoupper($data['fornecedor']['codigo']);
        $fornecedor->cnpj          = mb_strtoupper($data['fornecedor']['cnpj']);
        $fornecedor->razao_social  = mb_strtoupper($data['fornecedor']['razao_social']);
        $fornecedor->status        = $data['fornecedor']['status'] ?? 'A';
        $fornecedor->save();

        return ['status' => true, 'data' => $fornecedor];
    }
}

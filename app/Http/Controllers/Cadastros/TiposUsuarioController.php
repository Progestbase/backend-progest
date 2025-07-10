<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\TiposUsuario;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

Class TiposUsuarioController 
{
    public function add(Request $request) {
        $data = $request->all();
        
        $validator = Validator::make($data['tiposUsuario'], [
            'nome'  => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $tiposUsuario = new TiposUsuario;
        $tiposUsuario->nome         = mb_strtoupper($data['tiposUsuario']['nome']);
        $tiposUsuario->descricao    = $data['tiposUsuario']['descricao'] ? $data['tiposUsuario']['descricao'] : '';
        $tiposUsuario->status       = $data['tiposUsuario']['status'] ? $data['tiposUsuario']['status'] : 'A';

        $tiposUsuario->save();

        return ['status' => true, 'data' => $tiposUsuario];
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        // $paginate = isset($data['paginate']) ? $data['paginate'] : 50;
        $tiposUsuario = $filters;
        $tiposUsuarioQuery = TiposUsuario::query();
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                // Aplica cada condição como cláusula where
                $tiposUsuarioQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $tiposUsuario = $tiposUsuarioQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                // ->paginate($paginate)
                ->get();
                ;
        } else {
            $tiposUsuario = $tiposUsuarioQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        }  
        
        return ['status' => true, 'data' => $tiposUsuario];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        DB::enableQueryLog();

        $tiposUsuario = TiposUsuario::find($dataID);

        return ['status' => true, 'data' => $tiposUsuario, 'query' => DB::getQueryLog()];

    }
}
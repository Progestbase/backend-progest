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

Class UnidadesController 
{
    public function add(Request $request) {
        $data = $request->all();
        
        $validator = Validator::make($data['unidades'], [
            'nome'          => 'required|string|max:255',
            'codigo_unidade'=> 'required|string|max:50|unique:unidades,codigo_unidade',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $unidades = new Unidades;
        $unidades->nome           = mb_strtoupper($data['unidades']['nome']);
        $unidades->codigo_unidade = mb_strtoupper($data['unidades']['codigo_unidade']);
        $unidades->descricao      = $data['unidades']['descricao'] ? $data['unidades']['descricao'] : '';
        $unidades->status         = $data['unidades']['status'] ? $data['unidades']['status'] : 'A';

        $unidades->save();

        return ['status' => true, 'data' => $unidades];
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        // $paginate = isset($data['paginate']) ? $data['paginate'] : 50;
        $unidades = $filters;
        $unidadesQuery = Unidades::query();
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                // Aplica cada condição como cláusula where
                $unidadesQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $unidades = $unidadesQuery
                ->select('id', 'nome', 'codigo_unidade', 'descricao', 'status')
                ->orderBy('nome')
                // ->paginate($paginate)
                ->get();
                ;
        } else {
            $unidades = $unidadesQuery
                ->select('id', 'nome', 'codigo_unidade', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        }  
        
        return ['status' => true, 'data' => $unidades];

    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        DB::enableQueryLog();

        $unidades = Unidades::find($dataID);

        return ['status' => true, 'data' => $unidades, 'query' => DB::getQueryLog()];

    }
}
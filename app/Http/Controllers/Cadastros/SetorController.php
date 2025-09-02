<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Setor;

use Illuminate\Support\Facades\DB;  

class SetorController
{
    public function add(Request $request){
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'codigo_setor' => 'required|string|max:50|unique:setores,codigo_setor',
            'descricao' => 'nullable|string|max:500',
            'status' => 'required|in:A,I',
            // 'estoque' => 'required|in:S,N',
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'errors' => $validator->errors()
            ];
        }

        $setor = new Setor();
        $setor->nome = $request->input('nome');
        $setor->codigo_setor = $request->input('codigo_setor');
        $setor->descricao = $request->input('descricao');
        $setor->status = $request->input('status');
        // $setor->estoque = $request->input('estoque');
        $setor->save();

        return [
            'status' => 'success',
            'data' => $setor
        ];
    }

    public function listAll(Request $request){
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $setores = $filters;
        $setoresQuery = Setor::query();
        foreach ($filters as $condition) {
            foreach ($condition as $field => $value) {
                $setoresQuery->where($field, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $setores = $setoresQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        } else {
            $setores = $setoresQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => 'success', 'data' => $setores]; 
    }

    public function listData(Request $request){

    }

    public function update(Request $request){

    }

    public function delete(Request $request){

    }
}
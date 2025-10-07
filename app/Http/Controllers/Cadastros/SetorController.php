<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Setor;
use Hamcrest\Core\Set;
use Illuminate\Support\Facades\DB;  

class SetorController
{
    public function add(Request $request){
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
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
        $setor->codigo_setor = $request->input('codigo_setor') ?? '';
        $setor->descricao = $request->input('descricao') ?? '';
        $setor->status = $request->input('status') ?? 'A';
        $setor->estoque = $request->input('estoque') ?? 'N';
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
                ->select('id', 'nome', 'codigo_setor', 'descricao', 'status', 'estoque')
                ->orderBy('nome')
                ->get();
        } else {
            $setores = $setoresQuery
                ->select('id', 'nome', 'codigo_setor', 'descricao', 'status', 'estoque')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => 'success', 'data' => $setores]; 
    }

    public function listData(Request $request){
        $setor = Setor::find($request->input('id'));
        if (!$setor) {
            return ['status' => 'error', 'message' => 'Setor não Encontrado'];
        }
        return ['status' => 'success', 'data' => $setor];
    }

    public function update(Request $request){
        $data = $request->all();
        $setor = Setor::find($data['id']);
        if (!$setor) {
            return ['status' => 'error', 'message' => 'Setor não Encontrado'];
        }

        $setor->update([
            'nome' => mb_strtoupper($data['nome']),
            'codigo_setor' => $data['codigo_setor'] ?? '',
            'descricao' => $data['descricao'] ?? '',
            'status' => $data['status'] ?? 'A',
            'estoque' => $data['estoque'] ?? 'N',
        ]);

        return [
            'status' => 'success',
            'data' => $setor
        ];
    }

    public function delete($id){

        $setor = Setor::find($id);
        if (!$setor) {
            return ['status' => 'error', 'message' => 'Setor não Encontrado'];
        }

        $setor->delete();

        return [
            'status' => 'success',
            'message' => 'Setor deletado com sucesso'
        ];
    }
}
<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Setor;
use Illuminate\Support\Facades\DB;

class SetorController
{
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'codigo_setor' => 'required|string|max:50|unique:setores,codigo_setor',
            'descricao' => 'nullable|string|max:500',
            'status' => 'required|in:A,I',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $setor = new Setor();
        $setor->nome = $request->input('nome');
        $setor->codigo_setor = $request->input('codigo_setor');
        $setor->descricao = $request->input('descricao');
        $setor->status = $request->input('status');
        $setor->save();

        return response()->json([
            'status' => 'success',
            'data' => $setor
        ], 201);
    }

    public function listAll(Request $request)
    {
        $filters = $request->input('filters', []);

        $query = Setor::query();

        foreach ($filters as $condition) {
            foreach ($condition as $field => $value) {
                $query->where($field, $value);
            }
        }

        $setores = $query->select('id', 'nome', 'descricao', 'status')
            ->orderBy('nome')
            ->get();

        return response()->json(['status' => 'success', 'data' => $setores]);
    }

    public function listData(Request $request)
    {
        $id = $request->input('id');
        $setor = Setor::find($id);

        if (!$setor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Setor não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $setor
        ]);
    }

    public function update(Request $request)
    {
        $id = $request->input('id');
        $setor = Setor::find($id);

        if (!$setor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Setor não encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'codigo_setor' => 'required|string|max:50|unique:setores,codigo_setor,' . $id,
            'descricao' => 'nullable|string|max:500',
            'status' => 'required|in:A,I',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $setor->nome = $request->input('nome');
        $setor->codigo_setor = $request->input('codigo_setor');
        $setor->descricao = $request->input('descricao');
        $setor->status = $request->input('status');
        $setor->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Setor atualizado com sucesso',
            'data' => $setor
        ]);
    }

    public function delete(Request $request, $id)
    {
        $setor = Setor::find($id);

        if (!$setor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Setor não encontrado'
            ], 404);
        }

        $setor->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Setor excluído com sucesso',
            'id' => $id
        ], 200);
    }
}

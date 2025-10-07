<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\UnidadeMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UnidadeMedidaController
{
    public function add(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['unidadeMedida'] ?? [], [
            'nome' => 'required|string|max:255',
            'quantidade_unidade_minima' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,  
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $um = new UnidadeMedida;
        $um->nome = mb_strtoupper($data['unidadeMedida']['nome']);
        $um->quantidade_unidade_minima = $data['unidadeMedida']['quantidade_unidade_minima'];
        $um->status = $data['unidadeMedida']['status'] ?? 'A';
        $um->save();

        return ['status' => true, 'data' => $um];
    }

    public function update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['unidadeMedida'] ?? [], [
            'id' => 'required|exists:unidade_medida,id',
            'nome' => 'required|string|max:255',
            'quantidade_unidade_minima' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $um = UnidadeMedida::find($data['unidadeMedida']['id']);
        if (!$um) {
            return response()->json(['status' => false, 'message' => 'Unidade de medida não encontrada.'], 404);
        }

        $um->nome = mb_strtoupper($data['unidadeMedida']['nome']);
        $um->quantidade_unidade_minima = $data['unidadeMedida']['quantidade_unidade_minima'];
        $um->status = $data['unidadeMedida']['status'] ?? $um->status;
        $um->save();

        return ['status' => true, 'data' => $um];
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $query = UnidadeMedida::query();
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $query->where($column, $value);
            }
        }

        $result = $query->select('id', 'nome', 'quantidade_unidade_minima', 'status')
            ->orderBy('nome')
            ->get();

        return ['status' => true, 'data' => $result];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $id = $data['id'] ?? null;

        if (!$id) {
            return response()->json(['status' => false, 'message' => 'ID não informado'], 400);
        }

        DB::enableQueryLog();
        $um = UnidadeMedida::find($id);

        return ['status' => true, 'data' => $um, 'query' => DB::getQueryLog()];
    }

    public function delete($id)
    {
        $um = UnidadeMedida::find($id);
        if (!$um) {
            return response()->json(['status' => false, 'message' => 'Unidade de medida não encontrada.'], 404);
        }

        // verificar referências se necessário (ex: produtos)
        $productCount = DB::table('produtos')->where('unidade_medida_id', $id)->count();
        if ($productCount > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Não é possível excluir: existem produtos vinculados a esta unidade.',
                'references' => ['produtos (' . $productCount . ')']
            ], 422);
        }

        $um->delete();

        return response()->json(['status' => true, 'message' => 'Unidade de medida excluída com sucesso.'], 200);
    }
}

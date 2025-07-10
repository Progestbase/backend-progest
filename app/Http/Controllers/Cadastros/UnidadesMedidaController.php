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
use App\Models\UnidadesMedida;

Class UnidadesMedidaController
{
    public function add(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data['unidadesMedida'], [
            'nome' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $unidade = new UnidadesMedida;
        $unidade->nome = mb_strtoupper($data['unidadesMedida']['nome']);
        $unidade->descricao = $data['unidadesMedida']['descricao'] ?? '';
        $status = $data['unidadesMedida']['status'] ?? 'A';
        if ($status === 'ativo') $status = 'A';
        if ($status === 'inativo') $status = 'I';
        $unidade->status = $status;
        $unidade->save();

        return ['status' => true, 'data' => $unidade];
    }

    public function update(Request $request) {
        $data = $request->all();
        $validator = Validator::make($data['unidadesMedida'], [
            'id' => 'required|exists:unidades_medida,id',
            'nome' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $unidade = UnidadesMedida::find($data['unidadesMedida']['id']);
        $unidade->nome = mb_strtoupper($data['unidadesMedida']['nome']);
        $unidade->descricao = $data['unidadesMedida']['descricao'] ?? '';
        $status = $data['unidadesMedida']['status'] ?? 'A';
        if ($status === 'ativo') $status = 'A';
        if ($status === 'inativo') $status = 'I';
        $unidade->status = $status;
        $unidade->save();

        return ['status' => true, 'data' => $unidade];
    }

    public function listData(Request $request) {
        $data = $request->all();
        $dataID = $data['id'];
        DB::enableQueryLog();
        $unidade = UnidadesMedida::find($dataID);
        return ['status' => true, 'data' => $unidade, 'query' => DB::getQueryLog()];
    }

    public function listAll(Request $request) {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $unidadesMedida = $filters;
        $unidadesMedidaQuery = UnidadesMedida::query();
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $unidadesMedidaQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $unidadesMedida = $unidadesMedidaQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        } else {
            $unidadesMedida = $unidadesMedidaQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => true, 'data' => $unidadesMedida];
    }
}
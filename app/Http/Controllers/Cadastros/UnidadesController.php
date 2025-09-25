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

class UnidadesController
{
    public function add(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['unidades'], [
            'nome'          => 'required|string|max:255',
            'codigo_unidade' => 'required|string|max:50|unique:unidades,codigo_unidade',
            'estoque'       => 'sometimes|boolean',
            'tipo'          => 'sometimes|in:Medicamento,Material',
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
        $unidades->estoque        = $data['unidades']['estoque'] ?? false;
        $unidades->tipo           = $data['unidades']['tipo'] ?? 'Material';

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
                ->select('id', 'nome', 'codigo_unidade', 'descricao', 'status', 'estoque', 'tipo')
                ->orderBy('nome')
                // ->paginate($paginate)
                ->get();;
        } else {
            $unidades = $unidadesQuery
                ->select('id', 'nome', 'codigo_unidade', 'descricao', 'status', 'estoque', 'tipo')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => true, 'data' => $unidades];
    }

    public function update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['unidades'], [
            'nome'          => 'required|string|max:255',
            'codigo_unidade' => 'required|string|max:50|unique:unidades,codigo_unidade,' . $data['unidades']['id'],
            'estoque'       => 'sometimes|boolean',
            'tipo'          => 'sometimes|in:Medicamento,Material',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $unidades = Unidades::find($data['unidades']['id']);

        if (!$unidades) {
            return response()->json([
                'status' => false,
                'message' => 'Unidade não encontrada.'
            ], 404);
        }

        $unidades->nome           = mb_strtoupper($data['unidades']['nome']);
        $unidades->codigo_unidade = mb_strtoupper($data['unidades']['codigo_unidade']);
        $unidades->descricao      = $data['unidades']['descricao'] ? $data['unidades']['descricao'] : '';
        $unidades->status         = $data['unidades']['status'] ? $data['unidades']['status'] : 'A';
        $unidades->estoque        = $data['unidades']['estoque'] ?? $unidades->estoque;
        $unidades->tipo           = $data['unidades']['tipo'] ?? $unidades->tipo;

        $unidades->save();

        return ['status' => true, 'data' => $unidades];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        $unidades = Unidades::find($dataID);

        return ['status' => true, 'data' => $unidades];
    }

    public function delete($id)
    {
        $unidades = Unidades::find($id);

        if (!$unidades) {
            return response()->json([
                'status' => false,
                'message' => 'Unidade não encontrada.'
            ], 404);
        }

        // Verificar usuários
        $references = $this->checkUnidadesReferences($id);
        if (!empty($references)) {
            return response()->json([
                'status' => false,
                'message' => 'Não é possível excluir esta unidade pois ela possui registros relacionados no sistema.',
                'references' => $references
            ], 422);
        }
        $unidades->delete();

        return response()->json([
            'status' => true,
            'message' => 'Unidade excluída com sucesso.'
        ], 200);
    }

    private function checkUnidadesReferences($id)
    {
        $references = [];

        // Verificar usuários através do relacionamento users -> setores -> unidades
        $userCount = DB::table('users')
            ->join('usuario_setor', 'users.id', '=', 'usuario_setor.user_id')
            ->join('setores', 'usuario_setor.setor_id', '=', 'setores.id')
            ->where('setores.unidade_id', $id)
            ->distinct('users.id')
            ->count();

        if ($userCount > 0) {
            $references[] = 'usuários (' . $userCount . ')';
        }

        // Verificar setores vinculados à unidade
        $setorCount = DB::table('setores')->where('unidade_id', $id)->count();
        if ($setorCount > 0) {
            $references[] = 'setores (' . $setorCount . ')';
        }

        return $references;
    }
}

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

class TiposUsuarioController
{
    public function add(Request $request)
    {
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

        // Verificar se nome já existe
        $existingNome = TiposUsuario::where('nome', mb_strtoupper($data['tiposUsuario']['nome']))->first();

        if ($existingNome) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => ['nome' => ['Este nome já está sendo usado por outro tipo de usuário.']]
            ], 422);
        }

        $tiposUsuario = new TiposUsuario;
        $tiposUsuario->nome         = mb_strtoupper($data['tiposUsuario']['nome']);
        $tiposUsuario->descricao    = $data['tiposUsuario']['descricao'] ? $data['tiposUsuario']['descricao'] : '';
        $tiposUsuario->status       = $data['tiposUsuario']['status'] ? $data['tiposUsuario']['status'] : 'A';

        $tiposUsuario->save();

        return response()->json([
            'status' => true,
            'message' => 'Tipo de usuário criado com sucesso!',
            'data' => $tiposUsuario
        ], 201);
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
                ->get();;
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

    public function update(Request $request)
    {
        $data = $request->tiposUsuario;

        $validator = Validator::make($data, [
            'id'        => 'required|exists:tipos_usuario,id',
            'nome'      => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'status'    => 'required|in:A,I'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $tiposUsuario = TiposUsuario::find($data['id']);
        if (!$tiposUsuario) {
            return response()->json([
                'status' => false,
                'message' => 'Tipo de usuário não encontrado.'
            ], 404);
        }

        $existingNome = TiposUsuario::where('nome', mb_strtoupper($data['nome']))
            ->where('id', '!=', $data['id'])
            ->first();

        if ($existingNome) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => ['nome' => ['Este nome já está sendo usado por outro tipo de usuário.']]
            ], 422);
        }

        $tiposUsuario->nome = mb_strtoupper($data['nome']);
        $tiposUsuario->descricao = $data['descricao'] ?? '';
        $tiposUsuario->status = $data['status'];

        $tiposUsuario->save();

        return response()->json([
            'status' => true,
            'message' => 'Tipo de usuário atualizado com sucesso!',
            'data' => $tiposUsuario
        ], 200);
    }

    public function delete($id)
    {
        $tiposUsuario = TiposUsuario::find($id);

        if (!$tiposUsuario) {
            return response()->json([
                'status' => false,
                'message' => 'Tipo de usuário não encontrado.'
            ], 404);
        }

        // Verificar se o usuário tem referências em outras tabelas
        $references = $this->checkTiposUsuarioReferences($id);

        if (!empty($references)) {
            return response()->json([
                'status' => false,
                'message' => 'Não é possível excluir este tipo de usuário pois ele possui registros relacionados no sistema.',
                'references' => $references
            ], 422);
        }

        $tiposUsuario->delete();

        return response()->json([
            'status' => true,
            'message' => 'Tipo de usuário excluído com sucesso.'
        ], 200);
    }

    private function checkTiposUsuarioReferences($id)
    {
        $references = [];

        // Verificar usuários
        $userCount = DB::table('users')->where('usuario_tipo', $id)->count();
        if ($userCount > 0) {
            $references[] = 'usuários (' . $userCount . ')';
        }

        return $references;
    }
}

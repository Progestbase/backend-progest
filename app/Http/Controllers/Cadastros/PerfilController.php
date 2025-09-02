<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Perfil;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PerfilController
{
    public function add(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['perfil'], [
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
        $existingNome = Perfil::where('nome', mb_strtoupper($data['perfil']['nome']))->first();

        if ($existingNome) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => ['nome' => ['Este nome já está sendo usado por outro tipo de usuário.']]
            ], 422);
        }

        $perfil = new Perfil;
        $perfil->nome         = mb_strtoupper($data['perfil']['nome']);
        $perfil->descricao    = $data['perfil']['descricao'] ? $data['perfil']['descricao'] : '';
        $perfil->status       = $data['perfil']['status'] ? $data['perfil']['status'] : 'A';

        $perfil->save();

        return response()->json([
            'status' => true,
            'message' => 'Perfil criado com sucesso!',
            'data' => $perfil   
        ], 201);
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];
        $perfis = $filters;
        $perfisQuery = Perfil::query();
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $perfisQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $perfis = $perfisQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        } else {
            $perfis = $perfisQuery
                ->select('id', 'nome', 'descricao', 'status')
                ->orderBy('nome')
                ->get();
        }

        return ['status' => true, 'data' => $perfis];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        DB::enableQueryLog();

        $perfis = Perfil::find($dataID);

        return ['status' => true, 'data' => $perfis, 'query' => DB::getQueryLog()];
    }

    public function update(Request $request)
    {
        $data = $request->perfis;

        $validator = Validator::make($data, [
            'id'        => 'required|exists:perfis,id',
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

        $perfis = Perfil::find($data['id']);
        if (!$perfis) {
            return response()->json([
                'status' => false,
                'message' => 'Perfil não encontrado.'
            ], 404);
        }

        $existingNome = Perfil::where('nome', mb_strtoupper($data['nome']))
            ->where('id', '!=', $data['id'])
            ->first();

        if ($existingNome) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => ['nome' => ['Este nome já está sendo usado por outro perfil.']]
            ], 422);
        }

        $perfis->nome = mb_strtoupper($data['nome']);
        $perfis->descricao = $data['descricao'] ?? '';
        $perfis->status = $data['status'];

        $perfis->save();

        return response()->json([
            'status' => true,
            'message' => 'Perfil atualizado com sucesso!',
            'data' => $perfis
        ], 200);
    }

    public function delete($id)
    {
        $perfis = Perfil::find($id);

        if (!$perfis) {
            return response()->json([
                'status' => false,
                'message' => 'Perfil não encontrado.'
            ], 404);
        }

        // Verificar se o usuário tem referências em outras tabelas
        $references = $this->checkPerfilReferences($id);

        if (!empty($references)) {
            return response()->json([
                'status' => false,
                'message' => 'Não é possível excluir este perfil pois ele possui registros relacionados no sistema.',
                'references' => $references
            ], 422);
        }

        $perfis->delete();

        return response()->json([
            'status' => true,
            'message' => 'Perfil excluído com sucesso.'
        ], 200);
    }

    private function checkPerfilReferences($id)
    {
        $references = [];

        // Verificar usuários
        $userCount = DB::table('users')->where('perfil', $id)->count();
        if ($userCount > 0) {
            $references[] = 'usuários (' . $userCount . ')';
        }

        return $references;
    }
}

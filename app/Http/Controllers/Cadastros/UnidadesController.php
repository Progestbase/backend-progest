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
            'polo_id'       => 'required|exists:polo,id',
            'nome'          => 'required|string|max:255',
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
        $unidades->polo_id        = $data['unidades']['polo_id'];
        $unidades->nome           = mb_strtoupper($data['unidades']['nome']);
        $unidades->descricao      = $data['unidades']['descricao'] ?? '';
        $unidades->status         = $data['unidades']['status'] ?? 'A';
        $unidades->estoque        = $data['unidades']['estoque'] ?? false;
        $unidades->tipo           = $data['unidades']['tipo'] ?? 'Material';

        $unidades->save();

        return ['status' => true, 'data' => $unidades];
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $unidadesQuery = Unidades::with('polo');

        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $unidadesQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $unidades = $unidadesQuery
                ->select('id', 'polo_id', 'nome', 'descricao', 'status', 'estoque', 'tipo')
                ->orderBy('nome')
                ->get();
        } else {
            $per_page = $data['per_page'] ?? 50;
            $unidades = $unidadesQuery
                ->select('id', 'polo_id', 'nome', 'descricao', 'status', 'estoque', 'tipo')
                ->orderBy('nome')
                ->paginate($per_page);
        }

        return ['status' => true, 'data' => $unidades];
    }

    public function update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['unidades'], [
            'polo_id'       => 'required|exists:polo,id',
            'nome'          => 'required|string|max:255',
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

        $unidades->polo_id        = $data['unidades']['polo_id'];
        $unidades->nome           = mb_strtoupper($data['unidades']['nome']);
        $unidades->descricao      = $data['unidades']['descricao'] ?? '';
        $unidades->status         = $data['unidades']['status'] ?? 'A';
        $unidades->estoque        = $data['unidades']['estoque'] ?? $unidades->estoque;
        $unidades->tipo           = $data['unidades']['tipo'] ?? $unidades->tipo;

        $unidades->save();

        return ['status' => true, 'data' => $unidades];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        $unidades = Unidades::with('polo')->find($dataID);

        if (!$unidades) {
            return response()->json([
                'status' => false,
                'message' => 'Unidade não encontrada.'
            ], 404);
        }

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

        // Verificar referências antes de deletar
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

    public function toggleStatus(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID da unidade não fornecido'
                ], 400);
            }

            $unidade = Unidades::find($data['id']);

            if (!$unidade) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unidade não encontrada'
                ], 404);
            }

            // Toggle: A -> I ou I -> A
            $unidade->status = $unidade->status === 'A' ? 'I' : 'A';
            $unidade->save();

            return response()->json([
                'status' => true,
                'data' => $unidade,
                'message' => 'Status atualizado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status da unidade: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao alterar status da unidade'
            ], 500);
        }
    }

    private function checkUnidadesReferences($id)
    {
        $references = [];

        // Verificar estoque vinculado à unidade
        $estoqueCount = DB::table('estoque')->where('unidade_id', $id)->count();
        if ($estoqueCount > 0) {
            $references[] = 'estoque (' . $estoqueCount . ' itens)';
        }

        // Verificar movimentações como origem
        $movOrigemCount = DB::table('movimentacao')->where('unidade_origem_id', $id)->count();
        if ($movOrigemCount > 0) {
            $references[] = 'movimentações de origem (' . $movOrigemCount . ')';
        }

        // Verificar movimentações como destino
        $movDestinoCount = DB::table('movimentacao')->where('unidade_destino_id', $id)->count();
        if ($movDestinoCount > 0) {
            $references[] = 'movimentações de destino (' . $movDestinoCount . ')';
        }

        return $references;
    }
}

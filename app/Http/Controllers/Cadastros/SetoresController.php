<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Setores;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SetoresController
{
    public function add(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['Setores'], [
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

        $Setores = new Setores;
        $Setores->polo_id        = $data['Setores']['polo_id'];
        $Setores->nome           = mb_strtoupper($data['Setores']['nome']);
        $Setores->descricao      = $data['Setores']['descricao'] ?? '';
        $Setores->status         = $data['Setores']['status'] ?? 'A';
        $Setores->estoque        = $data['Setores']['estoque'] ?? false;
        $Setores->tipo           = $data['Setores']['tipo'] ?? 'Material';

        $Setores->save();

        return ['status' => true, 'data' => $Setores];
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $SetoresQuery = Setores::with('polo');

        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $SetoresQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $Setores = $SetoresQuery
                ->select('id', 'polo_id', 'nome', 'descricao', 'status', 'estoque', 'tipo')
                ->orderBy('nome')
                ->get();
        } else {
            $per_page = $data['per_page'] ?? 50;
            $Setores = $SetoresQuery
                ->select('id', 'polo_id', 'nome', 'descricao', 'status', 'estoque', 'tipo')
                ->orderBy('nome')
                ->paginate($per_page);
        }

        return ['status' => true, 'data' => $Setores];
    }

    public function update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['Setores'], [
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

        $Setores = Setores::find($data['Setores']['id']);

        if (!$Setores) {
            return response()->json([
                'status' => false,
                'message' => 'Setor não encontrado.'
            ], 404);
        }

        $Setores->polo_id        = $data['Setores']['polo_id'];
        $Setores->nome           = mb_strtoupper($data['Setores']['nome']);
        $Setores->descricao      = $data['Setores']['descricao'] ?? '';
        $Setores->status         = $data['Setores']['status'] ?? 'A';
        $Setores->estoque        = $data['Setores']['estoque'] ?? $Setores->estoque;
        $Setores->tipo           = $data['Setores']['tipo'] ?? $Setores->tipo;

        $Setores->save();

        return ['status' => true, 'data' => $Setores];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        $Setores = Setores::with('polo')->find($dataID);

        if (!$Setores) {
            return response()->json([
                'status' => false,
                'message' => 'Setor não encontrado.'
            ], 404);
        }

        return ['status' => true, 'data' => $Setores];
    }

    public function delete($id)
    {
        $Setores = Setores::find($id);

        if (!$Setores) {
            return response()->json([
                'status' => false,
                'message' => 'Setor não encontrado.'
            ], 404);
        }

        // Verificar referências antes de deletar
        $references = $this->checkSetoresReferences($id);
        if (!empty($references)) {
            return response()->json([
                'status' => false,
                'message' => 'Não é possível excluir este setor pois ele possui registros relacionados no sistema.',
                'references' => $references
            ], 422);
        }

        $Setores->delete();

        return response()->json([
            'status' => true,
            'message' => 'Setor excluído com sucesso.'
        ], 200);
    }

    public function toggleStatus(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do setor não fornecido'
                ], 400);
            }

            $setor = Setores::find($data['id']);

            if (!$setor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Setor não encontrado'
                ], 404);
            }

            // Toggle: A -> I ou I -> A
            $setor->status = $setor->status === 'A' ? 'I' : 'A';
            $setor->save();

            return response()->json([
                'status' => true,
                'data' => $setor,
                'message' => 'Status atualizado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status do setor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao alterar status do setor'
            ], 500);
        }
    }

    private function checkSetoresReferences($id)
    {
        $references = [];

        // Verificar estoque vinculado ao setor
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

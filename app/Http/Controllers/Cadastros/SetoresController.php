<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Setores;
use App\Models\User;
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
            'unidade_id'       => 'required|exists:unidades,id',
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
        $Setores->unidade_id        = $data['Setores']['unidade_id'];
        $Setores->nome           = mb_strtoupper($data['Setores']['nome']);
        $Setores->descricao      = $data['Setores']['descricao'] ?? '';
        $Setores->status         = $data['Setores']['status'] ?? 'A';
        $Setores->estoque        = $data['Setores']['estoque'] ?? false;
        $Setores->tipo           = $data['Setores']['tipo'] ?? 'Material';

        try {
            DB::beginTransaction();

            $Setores->save();

            // Se enviar dados de fornecedor junto com a criação do setor
            // Esperamos um payload opcional: $data['fornecedor'] => ['setor_id' => <id opcional>, 'tipo_produto' => 'Medicamento'|'Material']
            if (isset($data['fornecedor']) && is_array($data['fornecedor'])) {
                $fornecedorData = $data['fornecedor'];

                $validatorFornecedor = Validator::make($fornecedorData, [
                    'setor_id' => 'sometimes|exists:setores,id',
                    // tipo_produto agora é opcional: se não for enviado, será inferido a partir do setor fornecedor
                    'tipo_produto' => 'sometimes|in:Medicamento,Material'
                ]);

                if ($validatorFornecedor->fails()) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'validacao' => true,
                        'erros' => $validatorFornecedor->errors()
                    ], 422);
                }

                // Determinar tipo_produto: usar enviado ou inferir a partir do setor fornecedor
                $fornecedorSetorId = $fornecedorData['setor_id'] ?? null;
                if (!$fornecedorSetorId) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'ID do setor fornecedor não informado.'
                    ], 422);
                }

                $setorFornecedorRow = DB::table('setores')->where('id', $fornecedorSetorId)->first();
                if (!$setorFornecedorRow) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Setor fornecedor informado não existe.'
                    ], 422);
                }

                // Se front não enviou tipo_produto, inferimos do setor fornecedor
                if (!isset($fornecedorData['tipo_produto'])) {
                    $fornecedorData['tipo_produto'] = $setorFornecedorRow->tipo;
                } else {
                    // Se enviou, validar que bate com o tipo do setor fornecedor
                    if ($fornecedorData['tipo_produto'] !== $setorFornecedorRow->tipo) {
                        DB::rollBack();
                        return response()->json([
                            'status' => false,
                            'message' => 'O tipo_produto informado não corresponde ao tipo do setor fornecedor.'
                        ], 422);
                    }
                }

                // Verificar se já existe um fornecedor do mesmo tipo para este setor solicitante
                $exists = DB::table('setor_fornecedor')
                    ->where('setor_solicitante_id', $Setores->id)
                    ->where('tipo_produto', $fornecedorData['tipo_produto'])
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    return response()->json([
                        'status' => false,
                        'message' => 'Já existe um setor fornecedor cadastrado para este tipo de produto para o setor solicitante.'
                    ], 422);
                }
                DB::table('setor_fornecedor')->insert([
                    'setor_solicitante_id' => $Setores->id,
                    'setor_fornecedor_id' => $fornecedorSetorId,
                    'tipo_produto' => $fornecedorData['tipo_produto'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return ['status' => true, 'data' => $Setores];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar setor com fornecedor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao criar setor'
            ], 500);
        }
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        // Eager load fornecedores relacionados
        $SetoresQuery = Setores::with(['unidade', 'fornecedoresRelacionados.fornecedor']);

        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $SetoresQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $Setores = $SetoresQuery
                ->select('id', 'unidade_id', 'nome', 'descricao', 'status', 'estoque', 'tipo')
                ->orderBy('nome')
                ->get();
        } else {
            $per_page = $data['per_page'] ?? 50;
            $Setores = $SetoresQuery
                ->select('id', 'unidade_id', 'nome', 'descricao', 'status', 'estoque', 'tipo')
                ->orderBy('nome')
                ->paginate($per_page);
        }

        return ['status' => true, 'data' => $Setores];
    }

    public function update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['Setores'], [
            'unidade_id'       => 'required|exists:unidades,id',
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

        $Setores->unidade_id        = $data['Setores']['unidade_id'];
        $Setores->nome           = mb_strtoupper($data['Setores']['nome']);
        $Setores->descricao      = $data['Setores']['descricao'] ?? '';
        $Setores->status         = $data['Setores']['status'] ?? 'A';
        $Setores->estoque        = $data['Setores']['estoque'] ?? $Setores->estoque;
        $Setores->tipo           = $data['Setores']['tipo'] ?? $Setores->tipo;

        $Setores->save();

        // Se foram enviados fornecedores para atualizar/definir
        // Esperamos: $data['fornecedores'] = [ ['id'=> <opt existente id do relacionamento>, 'setor_fornecedor_id' => <setor id fornecedor>, 'tipo_produto' => 'Medicamento'|'Material'], ... ]
        if (isset($data['fornecedores']) && is_array($data['fornecedores'])) {
            try {
                DB::beginTransaction();

                $incoming = $data['fornecedores'];

                // Buscar relacionamentos atuais
                $current = DB::table('setor_fornecedor')->where('setor_solicitante_id', $Setores->id)->get();

                // Mapear ids existentes para facilitar operações
                $incomingIds = array_filter(array_map(function ($f) {
                    return $f['id'] ?? null;
                }, $incoming));

                // Deletar relações que não foram enviadas (removidas pelo cliente)
                foreach ($current as $cur) {
                    if (!in_array($cur->id, $incomingIds)) {
                        DB::table('setor_fornecedor')->where('id', $cur->id)->delete();
                    }
                }

                // Processar incoming: criar ou atualizar
                foreach ($incoming as $f) {
                    /** @var array $f */
                    $validatorF = Validator::make($f, [
                        'setor_fornecedor_id' => 'required|exists:setores,id',
                        'tipo_produto' => 'required|in:Medicamento,Material',
                    ]);

                    if ($validatorF->fails()) {
                        DB::rollBack();
                        return response()->json([
                            'status' => false,
                            'validacao' => true,
                            'erros' => $validatorF->errors()
                        ], 422);
                    }

                    // Verificar unicidade por tipo para este solicitante (exceto se for o próprio registro sendo atualizado)
                    $existsQuery = DB::table('setor_fornecedor')
                        ->where('setor_solicitante_id', $Setores->id)
                        ->where('tipo_produto', $f['tipo_produto']);

                    if (isset($f['id'])) {
                        $existsQuery->where('id', '!=', $f['id']);
                    }

                    if ($existsQuery->exists()) {
                        DB::rollBack();
                        return response()->json([
                            'status' => false,
                            'message' => 'Já existe um setor fornecedor deste tipo para este solicitante.'
                        ], 422);
                    }

                    if (isset($f['id'])) {
                        // Atualizar
                        DB::table('setor_fornecedor')->where('id', $f['id'])->update([
                            'setor_fornecedor_id' => $f['setor_fornecedor_id'],
                            'tipo_produto' => $f['tipo_produto'],
                            'updated_at' => now(),
                        ]);
                    } else {
                        // Criar
                        DB::table('setor_fornecedor')->insert([
                            'setor_solicitante_id' => $Setores->id,
                            'setor_fornecedor_id' => $f['setor_fornecedor_id'],
                            'tipo_produto' => $f['tipo_produto'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erro ao atualizar fornecedores do setor: ' . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => 'Erro ao atualizar fornecedores do setor'
                ], 500);
            }
        }

        return ['status' => true, 'data' => Setores::with(['unidade', 'fornecedoresRelacionados.fornecedor'])->find($Setores->id)];
    }

    public function listConsumers(Request $request)
    {
        try {
            /** @var array $data */
            $data = $request->all();

            if (!isset($data['id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do setor não fornecido'
                ], 400);
            }

            /** @var User|null $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            // Apenas admin ou almoxarife podem acessar
            $hasPermission = DB::table('usuario_setor')
                ->where('usuario_id', $user->id)
                ->where('setor_id', $data['id'])
                ->whereIn('perfil', ['admin', 'almoxarife'])
                ->exists();

            if (!$hasPermission && !$user->isSuperAdmin()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não tem permissão para acessar esta lista'
                ], 403);
            }

            // Verificar se setor existe
            $setor = Setores::find($data['id']);
            if (!$setor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Setor não encontrado'
                ], 404);
            }

            // Obter todos os consumidores (recursivo)
            $consumers = $this->getConsumersRecursive($data['id']);

            return response()->json([
                'status' => true,
                'data' => $consumers
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar consumidores do setor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao listar consumidores do setor'
            ], 500);
        }
    }

    private function getConsumersRecursive($setorFornecedorId, &$visited = [])
    {
        if (in_array($setorFornecedorId, $visited)) {
            return [];
        }

        $visited[] = $setorFornecedorId;

        // Buscar setores que têm este setor como fornecedor
        $directConsumers = DB::table('setor_fornecedor')
            ->where('setor_fornecedor_id', $setorFornecedorId)
            ->pluck('setor_solicitante_id')
            ->toArray();

        $consumers = [];

        // Para cada consumidor direto, buscar seus dados e seus consumidores
        foreach ($directConsumers as $consumerId) {
            $setor = Setores::with(['unidade'])->find($consumerId);

            if ($setor) {
                $consumers[] = [
                    'id' => $setor->id,
                    'unidade_id' => $setor->unidade_id,
                    'nome' => $setor->nome,
                    'descricao' => $setor->descricao,
                    'status' => $setor->status,
                    'estoque' => $setor->estoque,
                    'tipo' => $setor->tipo,
                    'unidade' => $setor->unidade,
                    'consumers' => $this->getConsumersRecursive($consumerId, $visited)
                ];
            }
        }

        return $consumers;
    }

    public function getDetail(Request $request)
    {
        try {
            /** @var array $data */
            $data = $request->all();

            if (!isset($data['id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do setor não fornecido'
                ], 400);
            }

            /** @var User|null $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            // Se não for super admin, verificar acesso na tabela usuario_setor
            if (!$user->isSuperAdmin()) {
                $hasAccess = DB::table('usuario_setor')
                    ->where('usuario_id', $user->id)
                    ->where('setor_id', $data['id'])
                    ->exists();

                if (!$hasAccess) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Usuário não tem acesso a este setor'
                    ], 403);
                }
            }

            $setor = Setores::with(['unidade', 'fornecedoresRelacionados.fornecedor'])->find($data['id']);

            if (!$setor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Setor não encontrado'
                ], 404);
            }

            // Transformar para garantir o formato esperado pelo frontend
            $result = $setor->toArray();

            $fornecedores = [];
            foreach ($setor->fornecedoresRelacionados as $rel) {
                $fornecedorObj = null;
                if ($rel->fornecedor) {
                    $fornecedorObj = [
                        'id' => $rel->fornecedor->id,
                        'nome' => $rel->fornecedor->nome ?? null,
                        'descricao' => $rel->fornecedor->descricao ?? null,
                        'tipo' => $rel->fornecedor->tipo ?? null,
                        'estoque' => isset($rel->fornecedor->estoque) ? (bool) $rel->fornecedor->estoque : null,
                    ];
                }

                $fornecedores[] = [
                    'id' => $rel->id,
                    'setor_fornecedor_id' => $rel->setor_fornecedor_id,
                    'tipo_produto' => $rel->tipo_produto,
                    'created_at' => $rel->created_at ? $rel->created_at->toDateTimeString() : null,
                    'updated_at' => $rel->updated_at ? $rel->updated_at->toDateTimeString() : null,
                    'fornecedor' => $fornecedorObj,
                ];
            }

            // Garantir chave consistente para o front
            $result['fornecedores_relacionados'] = $fornecedores;

            return response()->json([
                'status' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao obter detalhes do setor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao obter detalhes do setor'
            ], 500);
        }
    }

    public function listWithAccess(Request $request)
    {
        try {
            /** @var User|null $user */
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            // Super admin tem acesso a todos os setores
            if ($user->isSuperAdmin()) {
                $setores = Setores::with(['unidade'])
                    ->select('id', 'unidade_id', 'nome', 'descricao', 'status', 'estoque', 'tipo')
                    ->where('status', 'A')
                    ->orderBy('nome')
                    ->get();

                return response()->json([
                    'status' => true,
                    'data' => $setores
                ]);
            }

            // Usuário comum: busca setores via tabela usuario_setor
            $setores = Setores::with(['unidade'])
                ->select('setores.id', 'setores.unidade_id', 'setores.nome', 'setores.descricao', 'setores.status', 'setores.estoque', 'setores.tipo')
                ->join('usuario_setor', 'setores.id', '=', 'usuario_setor.setor_id')
                ->where('usuario_setor.usuario_id', $user->id)
                ->where('setores.status', 'A')
                ->orderBy('setores.nome')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $setores
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar setores com acesso: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao listar setores'
            ], 500);
        }
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        // Carregar setor com fornecedores relacionados e dados do fornecedor
        $Setores = Setores::with(['unidade', 'fornecedoresRelacionados.fornecedor'])->find($dataID);

        if (!$Setores) {
            return response()->json([
                'status' => false,
                'message' => 'Setor não encontrado.'
            ], 404);
        }

        // Transformar para garantir o formato esperado pelo frontend
        $result = $Setores->toArray();

        $fornecedores = [];
        foreach ($Setores->fornecedoresRelacionados as $rel) {
            $fornecedorObj = null;
            if ($rel->fornecedor) {
                $fornecedorObj = [
                    'id' => $rel->fornecedor->id,
                    'nome' => $rel->fornecedor->nome ?? null,
                    'descricao' => $rel->fornecedor->descricao ?? null,
                    'tipo' => $rel->fornecedor->tipo ?? null,
                    'estoque' => isset($rel->fornecedor->estoque) ? (bool) $rel->fornecedor->estoque : null,
                ];
            }

            $fornecedores[] = [
                'id' => $rel->id,
                'setor_fornecedor_id' => $rel->setor_fornecedor_id,
                'tipo_produto' => $rel->tipo_produto,
                'created_at' => $rel->created_at ? $rel->created_at->toDateTimeString() : null,
                'updated_at' => $rel->updated_at ? $rel->updated_at->toDateTimeString() : null,
                'fornecedor' => $fornecedorObj,
            ];
        }

        // Garantir chave consistente para o front
        $result['fornecedores_relacionados'] = $fornecedores;

        return ['status' => true, 'data' => $result];
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
    public function addFornecedor(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'setor_solicitante_id' => 'required|exists:setores,id',
                'setor_fornecedor_id' => 'required|exists:setores,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            // Verificar se o setor fornecedor tem controle de estoque
            $setorFornecedor = DB::table('setores')->where('id', $data['setor_fornecedor_id'])->first();
            if (!$setorFornecedor || !$setorFornecedor->estoque) {
                return response()->json([
                    'status' => false,
                    'message' => 'O setor selecionado não possui controle de estoque e não pode ser um fornecedor.'
                ], 422);
            }

            // Verificar se já existe
            $exists = DB::table('setor_fornecedor')
                ->where('setor_solicitante_id', $data['setor_solicitante_id'])
                ->where('setor_fornecedor_id', $data['setor_fornecedor_id'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Este fornecedor já está vinculado a este setor.'
                ], 422);
            }

            DB::table('setor_fornecedor')->insert([
                'setor_solicitante_id' => $data['setor_solicitante_id'],
                'setor_fornecedor_id' => $data['setor_fornecedor_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Fornecedor adicionado com sucesso.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar fornecedor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao adicionar fornecedor.'
            ], 500);
        }
    }

    public function removeFornecedor(Request $request)
    {
        try {
            $data = $request->all();

            // Aceita ID do relacionamento OU par de IDs
            if (isset($data['id'])) {
                DB::table('setor_fornecedor')->where('id', $data['id'])->delete();
            } elseif (isset($data['setor_solicitante_id']) && isset($data['setor_fornecedor_id'])) {
                DB::table('setor_fornecedor')
                    ->where('setor_solicitante_id', $data['setor_solicitante_id'])
                    ->where('setor_fornecedor_id', $data['setor_fornecedor_id'])
                    ->delete();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Dados insuficientes para remoção.'
                ], 400);
            }

            return response()->json([
                'status' => true,
                'message' => 'Fornecedor removido com sucesso.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao remover fornecedor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao remover fornecedor.'
            ], 500);
        }
    }

    /**
     * Lista os setores fornecedores disponíveis para um setor solicitante.
     * Usado no formulário de movimentações para popular o dropdown de origem.
     */
    public function listFornecedoresParaSetor(Request $request)
    {
        try {
            $data = $request->all();

            if (!isset($data['setor_id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do setor não fornecido'
                ], 400);
            }

            $setorId = $data['setor_id'];

            // Verificar se o setor existe
            $setor = Setores::find($setorId);
            if (!$setor) {
                return response()->json([
                    'status' => false,
                    'message' => 'Setor não encontrado'
                ], 404);
            }

            // Buscar fornecedores relacionados a este setor (como solicitante)
            $fornecedores = DB::table('setor_fornecedor')
                ->join('setores', 'setores.id', '=', 'setor_fornecedor.setor_fornecedor_id')
                ->where('setor_fornecedor.setor_solicitante_id', $setorId)
                ->where('setores.status', 'A')
                ->select(
                    'setores.id',
                    'setores.nome',
                    'setores.tipo',
                    'setores.estoque',
                    'setor_fornecedor.id as relacao_id'
                )
                ->orderBy('setores.nome')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $fornecedores
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar fornecedores para setor: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro ao listar fornecedores'
            ], 500);
        }
    }
}

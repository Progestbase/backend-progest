<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimentacao;
use App\Models\ItemMovimentacao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MovimentacaoController extends Controller
{
    // Criar movimentação (pode ser rascunho ou pendente)
    public function store(Request $request)
    {
        $data = $request->only(['usuario_id', 'setor_origem_id', 'setor_destino_id', 'tipo', 'observacao', 'status_solicitacao', 'itens']);

        // Normalizar itens: aceitar `quantidade` do front e mapear para `quantidade_solicitada`
        if (!empty($data['itens']) && is_array($data['itens'])) {
            foreach ($data['itens'] as $k => $it) {
                // mapear aliases comuns
                if (isset($it['quantidade']) && !isset($it['quantidade_solicitada'])) {
                    $data['itens'][$k]['quantidade_solicitada'] = $it['quantidade'];
                }
                if (isset($it['produtoId']) && !isset($it['produto_id'])) {
                    $data['itens'][$k]['produto_id'] = $it['produtoId'];
                }
            }
        }

        $validator = Validator::make($data, [
            'usuario_id' => 'required|integer|exists:users,id',
            'tipo' => 'required|in:T,D,S',
            'status_solicitacao' => 'nullable|in:A,R,P,C',
            'setor_origem_id' => 'nullable|integer|exists:setores,id',
            'setor_destino_id' => 'nullable|integer|exists:setores,id',
            'itens' => 'nullable|array',
            'itens.*.produto_id' => 'required_with:itens|integer|exists:produtos,id',
            'itens.*.quantidade_solicitada' => 'required_with:itens|numeric|min:0.0001'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            // tornar criação atômica: se falhar a criação dos itens, rollback
            $mov = DB::transaction(function () use ($data) {
                $mov = Movimentacao::create([
                    'usuario_id' => $data['usuario_id'],
                    'setor_origem_id' => $data['setor_origem_id'] ?? null,
                    'setor_destino_id' => $data['setor_destino_id'] ?? null,
                    'tipo' => $data['tipo'],
                    'data_hora' => now(),
                    'observacao' => $data['observacao'] ?? null,
                    'status_solicitacao' => $data['status_solicitacao'] ?? 'P'
                ]);

                // criar itens (obrigatórios quando enviados)
                if (!empty($data['itens']) && is_array($data['itens'])) {
                    foreach ($data['itens'] as $it) {
                        ItemMovimentacao::create([
                            'movimentacao_id' => $mov->id,
                            'produto_id' => $it['produto_id'],
                            'quantidade_solicitada' => $it['quantidade_solicitada'] ?? 0,
                            'quantidade_liberada' => $it['quantidade_liberada'] ?? 0,
                            'lote' => $it['lote'] ?? null
                        ]);
                    }
                }

                return $mov;
            });

            return response()->json(['status' => true, 'data' => $mov], 201);
        } catch (\Exception $e) {
            Log::error('Erro criando movimentação: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['status' => false, 'message' => 'Erro ao criar movimentação', 'detail' => $e->getMessage()], 500);
        }
    }

    // Listar solicitações por setor (relacionada como origem OU destino)
    public function listBySetor(Request $request)
    {
        // Compatibilidade: aceite 'setor_id' (novo) ou 'unidade_id' (legado do front)
        $setorId = $request->input('setor_id') ?? $request->input('unidade_id');
        if (!$setorId) {
            return response()->json(['status' => false, 'message' => 'setor_id (ou unidade_id) é obrigatório'], 422);
        }

        // Regras: rascunho aparece apenas para o setor que solicitou (origem). Quando setor é destino, rascunhos não aparecem.
        $movs = Movimentacao::with(['usuario', 'setorOrigem', 'setorDestino', 'itens'])
            ->where(function ($q) use ($setorId) {
                $q->where('setor_origem_id', $setorId)
                    ->orWhere('setor_destino_id', $setorId);
            })
            ->orderBy('data_hora', 'desc')
            ->get()
            ->filter(function ($m) use ($setorId) {
                if ($m->status_solicitacao === 'C') { // rascunho
                    return $m->setor_origem_id == $setorId; // só mostrar se o setor for origem
                }
                return true;
            })
            ->values()
            ->map(function ($m) {
                // calcular quantidade de produtos distintos na movimentação
                $distinctCount = 0;
                if ($m->relationLoaded('itens') && $m->itens->isNotEmpty()) {
                    $distinctCount = $m->itens->pluck('produto_id')->unique()->count();
                }
                $m->itens_diferentes_count = $distinctCount;
                return $m;
            });

        return response()->json(['status' => true, 'data' => $movs]);
    }

    // Detalhes / itens da movimentação
    public function show($id)
    {
        $mov = Movimentacao::with(['itens.produto', 'usuario', 'setorOrigem', 'setorDestino'])->find($id);
        if (!$mov) {
            return response()->json(['status' => false, 'message' => 'Movimentação não encontrada'], 404);
        }
        return response()->json(['status' => true, 'data' => $mov]);
    }

    // Processar movimentação: aprovar, reprovar, ou mover rascunho->pendente
    public function process(Request $request, $id)
    {
        $mov = Movimentacao::find($id);
        if (!$mov) return response()->json(['status' => false, 'message' => 'Movimentação não encontrada'], 404);

        $action = $request->input('action'); // 'approve','reject','submit'
        $aprovadorId = $request->input('aprovador_usuario_id');

        if (!in_array($action, ['approve', 'reject', 'submit'])) {
            return response()->json(['status' => false, 'message' => 'action inválida'], 422);
        }

        try {
            if ($action === 'approve') {
                $mov->status_solicitacao = 'A';
                $mov->aprovador_usuario_id = $aprovadorId;
                // aqui poderia vir lógica para alocar lotes e atualizar estoque
            } elseif ($action === 'reject') {
                $mov->status_solicitacao = 'R';
                $mov->aprovador_usuario_id = $aprovadorId;
            } elseif ($action === 'submit') {
                // sair de rascunho para pendente
                $mov->status_solicitacao = 'P';
            }
            $mov->save();
            return response()->json(['status' => true, 'data' => $mov]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['status' => false, 'message' => 'Erro ao processar movimentação'], 500);
        }
    }

    // Deletar apenas rascunhos
    public function destroy($id)
    {
        $mov = Movimentacao::find($id);
        if (!$mov) return response()->json(['status' => false, 'message' => 'Movimentação não encontrada'], 404);
        if ($mov->status_solicitacao !== 'C') {
            return response()->json(['status' => false, 'message' => 'Só é possível deletar movimentações em rascunho'], 403);
        }
        $mov->itens()->delete();
        $mov->delete();
        return response()->json(['status' => true]);
    }
}

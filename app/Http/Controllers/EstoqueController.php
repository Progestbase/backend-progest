<?php

namespace App\Http\Controllers;

use App\Models\Estoque;
use App\Models\Unidades;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EstoqueController extends Controller
{
    /**
     * Listar estoque por unidade com informações detalhadas do produto
     * 
     * @param int $unidadeId
     * @return JsonResponse
     */
    public function listarPorUnidade($unidadeId): JsonResponse
    {
        try {
            // Verificar se a unidade existe e possui estoque
            $unidade = Unidades::find($unidadeId);

            if (!$unidade) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unidade não encontrada.'
                ], 404);
            }

            if (!$unidade->estoque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta unidade não possui controle de estoque.'
                ], 400);
            }

            // Buscar estoque da unidade com informações do produto
            $estoque = Estoque::with([
                'produto' => function ($query) {
                    $query->select('id', 'nome', 'marca', 'codigo_simpras', 'codigo_barras', 'grupo_produto_id', 'unidade_medida_id', 'status');
                },
                'produto.grupoProduto' => function ($query) {
                    $query->select('id', 'nome', 'tipo', 'status');
                },
                'produto.unidadeMedida' => function ($query) {
                    $query->select('id', 'nome');
                }
            ])
                ->where('unidade_id', $unidadeId)
                ->get()
                ->map(function ($item) {
                    return [
                        'estoque_id' => $item->id,
                        'quantidade_atual' => $item->quantidade_atual,
                        'quantidade_minima' => $item->quantidade_minima,
                        'status_disponibilidade' => $item->status_disponibilidade,
                        'status_disponibilidade_texto' => $item->status_disponibilidade === 'D' ? 'Disponível' : 'Indisponível',
                        'abaixo_minimo' => $item->isAbaixoMinimo(),
                        'produto' => [
                            'id' => $item->produto->id,
                            'nome' => $item->produto->nome,
                            'nome_completo' => $item->produto->nome_completo,
                            'marca' => $item->produto->marca,
                            'codigo_simpras' => $item->produto->codigo_simpras,
                            'codigo_barras' => $item->produto->codigo_barras,
                            'status' => $item->produto->status,
                            'grupo_produto' => [
                                'id' => $item->produto->grupoProduto->id ?? null,
                                'nome' => $item->produto->grupoProduto->nome ?? null,
                                'tipo' => $item->produto->grupoProduto->tipo ?? null,
                            ],
                            'unidade_medida' => [
                                'id' => $item->produto->unidadeMedida->id ?? null,
                                'nome' => $item->produto->unidadeMedida->nome ?? null,
                            ]
                        ],
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'unidade' => [
                        'id' => $unidade->id,
                        'nome' => $unidade->nome,
                        // 'codigo_unidade' removido (coluna não existe na migration)
                        'tipo' => $unidade->tipo,
                    ],
                    'estoque' => $estoque,
                    'resumo' => [
                        'total_produtos' => $estoque->count(),
                        'produtos_disponiveis' => $estoque->where('status_disponibilidade', 'D')->count(),
                        'produtos_abaixo_minimo' => $estoque->where('abaixo_minimo', true)->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar estoque.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibir um item específico do estoque
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $estoque = Estoque::with([
                'produto.grupoProduto',
                'produto.unidadeMedida',
                'unidade'
            ])->find($id);

            if (!$estoque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item de estoque não encontrado.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $estoque->id,
                    'quantidade_atual' => $estoque->quantidade_atual,
                    'quantidade_minima' => $estoque->quantidade_minima,
                    'status_disponibilidade' => $estoque->status_disponibilidade,
                    'status_disponibilidade_texto' => $estoque->status_disponibilidade === 'D' ? 'Disponível' : 'Indisponível',
                    'abaixo_minimo' => $estoque->isAbaixoMinimo(),
                    'produto' => $estoque->produto,
                    'unidade' => $estoque->unidade,
                    'created_at' => $estoque->created_at,
                    'updated_at' => $estoque->updated_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar item do estoque.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar quantidade mínima do estoque
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function atualizarQuantidadeMinima(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'quantidade_minima' => 'required|integer|min:0'
            ]);

            $estoque = Estoque::find($id);

            if (!$estoque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item de estoque não encontrado.'
                ], 404);
            }

            $estoque->update([
                'quantidade_minima' => $request->quantidade_minima
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quantidade mínima atualizada com sucesso.',
                'data' => $estoque
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar quantidade mínima.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualizar status de disponibilidade do estoque
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function atualizarStatus(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'status_disponibilidade' => 'required|in:D,I'
            ]);

            $estoque = Estoque::find($id);

            if (!$estoque) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item de estoque não encontrado.'
                ], 404);
            }

            $estoque->update([
                'status_disponibilidade' => $request->status_disponibilidade
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status atualizado com sucesso.',
                'data' => $estoque
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

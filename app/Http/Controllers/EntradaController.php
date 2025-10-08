<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Estoque;
use App\Models\ItensEntrada;
use App\Models\Produto;
use App\Models\Unidades;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EntradaController extends Controller
{
    /**
     * Registrar uma nova entrada de produtos no estoque da unidade.
     */
    public function add(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'nota_fiscal' => 'required|string|max:255',
            'unidade_id' => 'required|exists:unidades,id',
            'fornecedor_id' => 'required|exists:fornecedores,id',
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|exists:produtos,id',
            'itens.*.quantidade' => 'required|integer|min:1',
        ], [
            'nota_fiscal.required' => 'A nota fiscal é obrigatória.',
            'unidade_id.required' => 'A unidade é obrigatória.',
            'unidade_id.exists' => 'Unidade não encontrada.',
            'fornecedor_id.required' => 'O fornecedor é obrigatório.',
            'fornecedor_id.exists' => 'Fornecedor não encontrado.',
            'itens.required' => 'Informe ao menos um item para a entrada.',
            'itens.array' => 'A lista de itens deve ser um array.',
            'itens.min' => 'Informe ao menos um item para a entrada.',
            'itens.*.produto_id.required' => 'Produto é obrigatório em todos os itens.',
            'itens.*.produto_id.exists' => 'Produto informado não foi encontrado.',
            'itens.*.quantidade.required' => 'Quantidade é obrigatória em todos os itens.',
            'itens.*.quantidade.integer' => 'Quantidade deve ser um número inteiro.',
            'itens.*.quantidade.min' => 'Quantidade deve ser ao menos 1.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $unidade = Unidades::find($data['unidade_id']);

        if (!$unidade->estoque) {
            return response()->json([
                'status' => false,
                'message' => 'A unidade selecionada não possui controle de estoque.'
            ], 400);
        }

        try {
            $entrada = DB::transaction(function () use ($data, $unidade) {
                $entrada = Entrada::create([
                    'nota_fiscal' => mb_strtoupper(trim($data['nota_fiscal'])),
                    'unidade_id' => $unidade->id,
                    'fornecedor_id' => $data['fornecedor_id'],
                ]);

                foreach ($data['itens'] as $item) {
                    $produto = Produto::with('grupoProduto')->find($item['produto_id']);

                    if (!$produto) {
                        throw new \RuntimeException('Produto não encontrado.');
                    }

                    if (!$produto->grupoProduto || $produto->grupoProduto->tipo !== $unidade->tipo) {
                        throw new \RuntimeException('Produto "' . $produto->nome . '" não é compatível com o tipo da unidade.');
                    }

                    $itemEntrada = ItensEntrada::create([
                        'entrada_id' => $entrada->id,
                        'produto_id' => $produto->id,
                        'quantidade' => $item['quantidade'],
                    ]);

                    $estoque = Estoque::firstOrCreate(
                        [
                            'produto_id' => $produto->id,
                            'unidade_id' => $unidade->id,
                        ],
                        [
                            'quantidade_atual' => 0,
                            'quantidade_minima' => 0,
                            'status_disponibilidade' => 'D',
                        ]
                    );

                    $estoque->quantidade_atual += $itemEntrada->quantidade;

                    if ($estoque->quantidade_atual > 0) {
                        $estoque->status_disponibilidade = 'D';
                    }

                    $estoque->save();
                }

                return $entrada;
            });

            $entrada->load(['unidade', 'fornecedor', 'itens.produto']);

            return response()->json([
                'status' => true,
                'message' => 'Entrada registrada com sucesso.',
                'data' => $entrada,
            ], 201);
        } catch (\RuntimeException $e) {
            Log::warning('Falha de validação na criação de entrada: ' . $e->getMessage(), [
                'payload' => $data,
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Erro ao registrar entrada: ' . $e->getMessage(), [
                'payload' => $data,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro interno ao registrar entrada.'
            ], 500);
        }
    }

    /**
     * Listar entradas com seus itens e detalhes dos produtos
     */
    public function list(Request $request)
    {
        try {
            $data = $request->all();
            $filters = $data['filters'] ?? [];
            $perPage = $data['per_page'] ?? 15;

            $query = Entrada::with([
                'unidade:id,nome,codigo_unidade,tipo',
                'fornecedor:id,razao_social_nome,tipo_pessoa,status',
                'itens.produto:id,nome,marca,grupo_produto_id,unidade_medida_id,status',
                'itens.produto.grupoProduto:id,nome,tipo',
                'itens.produto.unidadeMedida:id,nome',
            ])->orderByDesc('created_at');

            if (!empty($filters)) {
                foreach ($filters as $key => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }

                    switch ($key) {
                        case 'nota_fiscal':
                            $query->where('nota_fiscal', 'like', '%' . trim($value) . '%');
                            break;
                        case 'unidade_id':
                            $query->where('unidade_id', $value);
                            break;
                        case 'fornecedor_id':
                            $query->where('fornecedor_id', $value);
                            break;
                    }
                }
            }

            /** @var LengthAwarePaginator $entradas */
            $entradas = $query->paginate($perPage);

            $entradas->getCollection()->transform(function (Entrada $entrada) {
                return [
                    'id' => $entrada->id,
                    'nota_fiscal' => $entrada->nota_fiscal,
                    'created_at' => $entrada->created_at,
                    'unidade' => $entrada->unidade,
                    'fornecedor' => $entrada->fornecedor,
                    'itens' => $entrada->itens->map(function (ItensEntrada $item) {
                        return [
                            'id' => $item->id,
                            'quantidade' => $item->quantidade,
                            'produto' => [
                                'id' => $item->produto->id,
                                'nome' => $item->produto->nome,
                                'marca' => $item->produto->marca,
                                'status' => $item->produto->status,
                                'grupo_produto' => $item->produto->grupoProduto,
                                'unidade_medida' => $item->produto->unidadeMedida,
                            ],
                        ];
                    })->values(),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $entradas,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar entradas: ' . $e->getMessage(), [
                'payload' => $request->all(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro interno ao listar entradas.'
            ], 500);
        }
    }

    /**
     * Atualizar uma entrada existente e seus itens ajustando o estoque
     */
    public function update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'id' => 'required|exists:entrada,id',
            'nota_fiscal' => 'required|string|max:255',
            'unidade_id' => 'required|exists:unidades,id',
            'fornecedor_id' => 'required|exists:fornecedores,id',
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|exists:produtos,id',
            'itens.*.quantidade' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        $entrada = Entrada::with(['itens'])->find($data['id']);
        $unidade = Unidades::find($data['unidade_id']);

        if (!$unidade->estoque) {
            return response()->json([
                'status' => false,
                'message' => 'A unidade selecionada não possui controle de estoque.'
            ], 400);
        }

        try {
            $entradaAtualizada = DB::transaction(function () use ($data, $entrada, $unidade) {
                // Reverter estoque dos itens atuais
                foreach ($entrada->itens as $itemExistente) {
                    $estoque = Estoque::where('produto_id', $itemExistente->produto_id)
                        ->where('unidade_id', $entrada->unidade_id)
                        ->first();

                    if ($estoque) {
                        $estoque->quantidade_atual -= $itemExistente->quantidade;
                        if ($estoque->quantidade_atual < 0) {
                            $estoque->quantidade_atual = 0;
                        }
                        $estoque->status_disponibilidade = $estoque->quantidade_atual > 0 ? 'D' : 'I';
                        $estoque->save();
                    }
                }

                // Atualiza dados da entrada
                $entrada->update([
                    'nota_fiscal' => mb_strtoupper(trim($data['nota_fiscal'])),
                    'unidade_id' => $unidade->id,
                    'fornecedor_id' => $data['fornecedor_id'],
                ]);

                // Remove itens antigos
                ItensEntrada::where('entrada_id', $entrada->id)->delete();

                // Cadastra novos itens e atualiza estoque
                foreach ($data['itens'] as $item) {
                    $produto = Produto::with('grupoProduto')->find($item['produto_id']);

                    if (!$produto || !$produto->grupoProduto || $produto->grupoProduto->tipo !== $unidade->tipo) {
                        throw new \RuntimeException('Produto "' . $produto->nome . '" não é compatível com o tipo da unidade.');
                    }

                    $itemEntrada = ItensEntrada::create([
                        'entrada_id' => $entrada->id,
                        'produto_id' => $produto->id,
                        'quantidade' => $item['quantidade'],
                    ]);

                    $estoque = Estoque::firstOrCreate(
                        [
                            'produto_id' => $produto->id,
                            'unidade_id' => $unidade->id,
                        ],
                        [
                            'quantidade_atual' => 0,
                            'quantidade_minima' => 0,
                            'status_disponibilidade' => 'D',
                        ]
                    );

                    $estoque->quantidade_atual += $itemEntrada->quantidade;
                    $estoque->status_disponibilidade = $estoque->quantidade_atual > 0 ? 'D' : 'I';
                    $estoque->save();
                }

                return $entrada;
            });

            $entradaAtualizada->load(['unidade', 'fornecedor', 'itens.produto']);

            return response()->json([
                'status' => true,
                'message' => 'Entrada atualizada com sucesso.',
                'data' => $entradaAtualizada,
            ]);
        } catch (\RuntimeException $e) {
            Log::warning('Falha de validação na atualização de entrada: ' . $e->getMessage(), [
                'payload' => $data,
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar entrada: ' . $e->getMessage(), [
                'payload' => $data,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro interno ao atualizar entrada.'
            ], 500);
        }
    }

    /**
     * Remover uma entrada e reverter o estoque relacionado
     */
    public function delete(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'id' => 'required|exists:entrada,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validator->errors()
            ], 422);
        }

        try {
            DB::transaction(function () use ($data) {
                $entrada = Entrada::with('itens')->find($data['id']);

                foreach ($entrada->itens as $item) {
                    $estoque = Estoque::where('produto_id', $item->produto_id)
                        ->where('unidade_id', $entrada->unidade_id)
                        ->first();

                    if ($estoque) {
                        $estoque->quantidade_atual -= $item->quantidade;
                        if ($estoque->quantidade_atual < 0) {
                            $estoque->quantidade_atual = 0;
                        }
                        $estoque->status_disponibilidade = $estoque->quantidade_atual > 0 ? 'D' : 'I';
                        $estoque->save();
                    }
                }

                ItensEntrada::where('entrada_id', $entrada->id)->delete();
                $entrada->delete();
            });

            return response()->json([
                'status' => true,
                'message' => 'Entrada removida com sucesso.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao remover entrada: ' . $e->getMessage(), [
                'payload' => $data,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro interno ao remover entrada.'
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Movimentacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use \Illuminate\Support\Facades\DB;

class RelatoriosController extends Controller
{
    /**
     * Relatório de Entradas
     * POST /api/relatorios/entradas/list
     * 
     * Retorna lista paginada de entradas com filtros por:
     * - Data inicial/final
     * - Setor
     * - Fornecedor
     * - Nota fiscal
     */
    public function listEntradasReport(Request $request)
    {
        try {
            $data = $request->all();
            
            // Validação dos filtros
            $validator = Validator::make($data, [
                'filters.date_from' => 'nullable|date',
                'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
                'filters.setor_id' => 'nullable|exists:setores,id',
                'filters.fornecedor_id' => 'nullable|exists:fornecedores,id',
                'filters.nota_fiscal' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ], [
                'filters.date_to.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
                'filters.setor_id.exists' => 'Setor não encontrado.',
                'filters.fornecedor_id.exists' => 'Fornecedor não encontrado.',
                'per_page.integer' => 'Itens por página deve ser um número inteiro.',
                'per_page.min' => 'Itens por página deve ser ao menos 1.',
                'per_page.max' => 'Itens por página não pode exceder 100.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            // Query base com eager loading para evitar N+1
            $query = Entrada::with([
                'fornecedor:id,razao_social_nome,cnpj',
                'setor:id,nome,tipo',
                'itens.produto:id,nome,codigo_simpras,codigo_barras',
                'itens.produto.unidadeMedida:id,nome'
            ]);

            // Aplicar filtros se fornecidos
            $filters = $data['filters'] ?? [];

            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            if (!empty($filters['setor_id'])) {
                $query->where('setor_id', $filters['setor_id']);
            }

            if (!empty($filters['fornecedor_id'])) {
                $query->where('fornecedor_id', $filters['fornecedor_id']);
            }

            if (!empty($filters['nota_fiscal'])) {
                $query->where('nota_fiscal', 'like', '%' . mb_strtoupper($filters['nota_fiscal']) . '%');
            }

            // Ordenação: mais recentes primeiro
            $query->orderByDesc('created_at');

            // Paginação
            $perPage = $data['per_page'] ?? 50;
            $paginated = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Relatório de entradas recuperado com sucesso',
                'data' => $paginated,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório de entradas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Erro ao gerar relatório de entradas'
            ], 500);
        }
    }

    /**
     * Relatório de Movimentações
     * POST /api/relatorios/movimentacoes/list
     * 
     * Retorna lista paginada de movimentações com filtros por:
     * - Data inicial/final
     * - Tipo (transferencia, saida, devolucao)
     * - Setor origem
     * - Setor destino
     * - Status
     */
    public function listMovimentacoesReport(Request $request)
    {
        try {
            $data = $request->all();
            
            // Validação dos filtros
            $validator = Validator::make($data, [
                'filters.date_from' => 'nullable|date',
                'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
                'filters.tipo' => 'nullable|string|in:transferencia,saida,devolucao',
                'filters.setor_origem_id' => 'nullable|exists:setores,id',
                'filters.setor_destino_id' => 'nullable|exists:setores,id',
                'filters.status' => 'nullable|string|in:A,I',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ], [
                'filters.date_to.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
                'filters.tipo.in' => 'Tipo de movimentação inválido. Use: transferencia, saida ou devolucao.',
                'filters.setor_origem_id.exists' => 'Setor de origem não encontrado.',
                'filters.setor_destino_id.exists' => 'Setor de destino não encontrado.',
                'filters.status.in' => 'Status inválido. Use: A (Ativo) ou I (Inativo).',
                'per_page.integer' => 'Itens por página deve ser um número inteiro.',
                'per_page.min' => 'Itens por página deve ser ao menos 1.',
                'per_page.max' => 'Itens por página não pode exceder 100.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            // Query base com eager loading para evitar N+1
            $query = Movimentacao::with([
                'setorOrigem:id,nome,tipo',
                'setorDestino:id,nome,tipo',
                'usuario:id,name,email',
                'aprovador:id,name,email',
                'itens.produto:id,nome,codigo_simpras,codigo_barras',
                'itens.produto.unidadeMedida:id,nome'
            ]);

            // Aplicar filtros se fornecidos
            $filters = $data['filters'] ?? [];

            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            if (!empty($filters['tipo'])) {
                $query->where('tipo', $filters['tipo']);
            }

            if (!empty($filters['setor_origem_id'])) {
                $query->where('setor_origem_id', $filters['setor_origem_id']);
            }

            if (!empty($filters['setor_destino_id'])) {
                $query->where('setor_destino_id', $filters['setor_destino_id']);
            }

            if (!empty($filters['status'])) {
                $query->where('status_solicitacao', $filters['status']);
            }

            // Ordenação: mais recentes primeiro
            $query->orderByDesc('created_at');

            // Paginação
            $perPage = $data['per_page'] ?? 50;
            $paginated = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Relatório de movimentações recuperado com sucesso',
                'data' => $paginated,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório de movimentações: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Erro ao gerar relatório de movimentações'
            ], 500);
        }
    }

    /**
     * Relatório de Saídas
     * POST /api/relatorios/saidas/list
     * 
     * Retorna lista paginada de movimentações do tipo 'S' (Saída) com filtros por:
     * - Data inicial/final (usando campo data_hora)
     * - Setor origem (quem solicitou a saída)
     * - Produto
     * - Status da solicitação
     */
    public function listSaidasReport(Request $request)
    {
        try {
            $data = $request->all();
            
            // Validação dos filtros
            $validator = Validator::make($data, [
                'filters.date_from' => 'nullable|date',
                'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
                'filters.setor_origem_id' => 'nullable|exists:setores,id',
                'filters.produto_id' => 'nullable|exists:produtos,id',
                'filters.status' => 'nullable|string|in:A,R,P,C,X',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ], [
                'filters.date_to.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
                'filters.setor_origem_id.exists' => 'Setor de origem não encontrado.',
                'filters.produto_id.exists' => 'Produto não encontrado.',
                'filters.status.in' => 'Status inválido. Use: A (Aprovado), R (Reprovado), P (Pendente), C (Rascunho), X (Cancelado).',
                'per_page.integer' => 'Itens por página deve ser um número inteiro.',
                'per_page.min' => 'Itens por página deve ser ao menos 1.',
                'per_page.max' => 'Itens por página não pode exceder 100.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            // Query base com eager loading para evitar N+1
            // Filtra apenas movimentações do tipo 'S' (Saída)
            $query = Movimentacao::with([
                'setorOrigem:id,nome,tipo',
                'setorDestino:id,nome,tipo',
                'usuario:id,name,email',
                'aprovador:id,name,email',
                'itens.produto:id,nome,codigo_simpras,codigo_barras',
                'itens.produto.unidadeMedida:id,nome',
                'itens.produto.grupoProduto:id,nome,tipo'
            ])->where('tipo', 'S');

            // Aplicar filtros se fornecidos
            $filters = $data['filters'] ?? [];

            if (!empty($filters['date_from'])) {
                $query->whereDate('data_hora', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('data_hora', '<=', $filters['date_to']);
            }

            if (!empty($filters['setor_origem_id'])) {
                $query->where('setor_origem_id', $filters['setor_origem_id']);
            }

            if (!empty($filters['produto_id'])) {
                // Filtrar por produto através da relação com itens
                $query->whereHas('itens', function ($q) use ($filters) {
                    $q->where('produto_id', $filters['produto_id']);
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status_solicitacao', $filters['status']);
            }

            // Ordenação: mais recentes primeiro
            $query->orderByDesc('data_hora');

            // Paginação
            $perPage = $data['per_page'] ?? 50;
            $paginated = $query->paginate($perPage);

            return response()->json([
                'status' => true,
                'message' => 'Relatório de saídas recuperado com sucesso',
                'data' => $paginated,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório de saídas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Erro ao gerar relatório de saídas'
            ], 500);
        }
    }

    /**
     * Relatório de Saídas por Data (Agregado por Produto)
     * POST /api/relatorios/saidas-por-data/list
     * 
     * Retorna lista consolidada de saídas agrupadas por data e produto.
     * Calcula a quantidade total de cada produto que saiu em cada dia,
     * somando todas as requisições de todos os setores.
     * 
     * Exemplo: Se saiu 10 dipironas para UTI e 15 para Emergência no mesmo dia,
     * o relatório mostrará 25 dipironas no total.
     * 
     * Filtros opcionais:
     * - Data inicial/final (se não informado, usa data atual)
     * - Setor origem (para filtrar por setor específico)
     * - Produto (para filtrar produto específico)
     */
    public function listSaidasPorData(Request $request)
    {
        try {
            $data = $request->all();
            
            // Validação dos filtros
            $validator = Validator::make($data, [
                'filters.date_from' => 'nullable|date',
                'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
                'filters.setor_id' => 'nullable|exists:setores,id',
                'filters.produto_id' => 'nullable|exists:produtos,id',
                'per_page' => 'nullable|integer|min:1|max:500',
                'page' => 'nullable|integer|min:1',
            ], [
                'filters.date_to.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
                'filters.setor_id.exists' => 'Setor não encontrado.',
                'filters.produto_id.exists' => 'Produto não encontrado.',
                'per_page.integer' => 'Itens por página deve ser um número inteiro.',
                'per_page.min' => 'Itens por página deve ser ao menos 1.',
                'per_page.max' => 'Itens por página não pode exceder 500.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            $filters = $data['filters'] ?? [];
            
            // Se não informar período, usa data atual
            $dateFrom = $filters['date_from'] ?? date('Y-m-d');
            $dateTo = $filters['date_to'] ?? $dateFrom;

            // Query agregada: agrupa por data e produto, soma quantidades
            $query = DB::table('item_movimentacao as im')
                ->join('movimentacao as m', 'im.movimentacao_id', '=', 'm.id')
                ->join('produtos as p', 'im.produto_id', '=', 'p.id')
                ->join('unidade_medida as um', 'p.unidade_medida_id', '=', 'um.id')
                ->join('grupo_produto as gp', 'p.grupo_produto_id', '=', 'gp.id')
                ->select(
                    DB::raw('DATE(m.data_hora) as data'),
                    'p.id as produto_id',
                    'p.nome as produto_nome',
                    'p.codigo_simpras',
                    'p.codigo_barras',
                    'um.nome as unidade_medida',
                    'gp.nome as grupo_produto',
                    'gp.tipo as tipo_produto',
                    DB::raw('SUM(im.quantidade_liberada) as quantidade_total'),
                    DB::raw('COUNT(DISTINCT m.id) as total_movimentacoes'),
                    DB::raw('COUNT(DISTINCT m.setor_origem_id) as total_setores')
                )
                ->where('m.tipo', 'S') // Apenas saídas
                ->where('m.status_solicitacao', 'A') // Apenas aprovadas
                ->whereDate('m.data_hora', '>=', $dateFrom)
                ->whereDate('m.data_hora', '<=', $dateTo);

            // Aplicar filtros opcionais
            if (!empty($filters['setor_id'])) {
                $query->where('m.setor_origem_id', $filters['setor_id']);
            }

            if (!empty($filters['produto_id'])) {
                $query->where('p.id', $filters['produto_id']);
            }

            // Agrupar por data e produto
            $query->groupBy(
                DB::raw('DATE(m.data_hora)'),
                'p.id',
                'p.nome',
                'p.codigo_simpras',
                'p.codigo_barras',
                'um.nome',
                'gp.nome',
                'gp.tipo'
            );

            // Paginação
            $perPage = $data['per_page'] ?? 50;
            $page = $data['page'] ?? 1;
            
            // Clonar query para count (sem ORDER BY que causa erro)
            $countQuery = clone $query;
            $total = $countQuery->get()->count();
            
            // Adicionar ordenação apenas na query de resultados
            $results = $query
                ->orderByDesc(DB::raw('DATE(m.data_hora)'))
                ->orderByDesc(DB::raw('SUM(im.quantidade_liberada)'))
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Agrupar resultados por data
            $groupedByDate = [];
            foreach ($results as $item) {
                $data = $item->data;
                
                if (!isset($groupedByDate[$data])) {
                    $groupedByDate[$data] = [
                        'data' => $data,
                        'produtos' => [],
                        'total_produtos' => 0,
                        'quantidade_total_dia' => 0
                    ];
                }
                
                // Buscar movimentações detalhadas (origem e destino) deste produto nesta data
                $movimentacoesDetalhadas = DB::table('item_movimentacao as im')
                    ->join('movimentacao as m', 'im.movimentacao_id', '=', 'm.id')
                    ->join('setores as so', 'm.setor_origem_id', '=', 'so.id')
                    ->leftJoin('setores as sd', 'm.setor_destino_id', '=', 'sd.id')
                    ->select(
                        'm.id as movimentacao_id',
                        'so.id as setor_origem_id',
                        'so.nome as setor_origem_nome',
                        'sd.id as setor_destino_id',
                        'sd.nome as setor_destino_nome',
                        'im.quantidade_liberada as quantidade',
                        'm.data_hora',
                        'm.observacao'
                    )
                    ->where('im.produto_id', $item->produto_id)
                    ->whereDate('m.data_hora', $data)
                    ->where('m.tipo', 'S')
                    ->where('m.status_solicitacao', 'A')
                    ->orderBy('m.data_hora', 'desc')
                    ->get();
                
                $groupedByDate[$data]['produtos'][] = [
                    'produto' => [
                        'id' => $item->produto_id,
                        'nome' => $item->produto_nome,
                        'codigo_simpras' => $item->codigo_simpras,
                        'codigo_barras' => $item->codigo_barras,
                        'unidade_medida' => $item->unidade_medida,
                        'grupo_produto' => $item->grupo_produto,
                        'tipo' => $item->tipo_produto,
                    ],
                    'quantidade_total' => (int) $item->quantidade_total,
                    'total_movimentacoes' => (int) $item->total_movimentacoes,
                    'movimentacoes' => $movimentacoesDetalhadas->map(function($mov) {
                        return [
                            'movimentacao_id' => $mov->movimentacao_id,
                            'quantidade' => (int) $mov->quantidade,
                            'setor_origem' => [
                                'id' => $mov->setor_origem_id,
                                'nome' => $mov->setor_origem_nome
                            ],
                            'setor_destino' => $mov->setor_destino_id ? [
                                'id' => $mov->setor_destino_id,
                                'nome' => $mov->setor_destino_nome
                            ] : null,
                            'data_hora' => $mov->data_hora,
                            'observacao' => $mov->observacao
                        ];
                    })
                ];
                
                $groupedByDate[$data]['total_produtos']++;
                $groupedByDate[$data]['quantidade_total_dia'] += (int) $item->quantidade_total;
            }
            
            // Converter para array indexado e ordenar por data
            $resultsWithDetails = array_values($groupedByDate);

            $paginationData = [
                'current_page' => $page,
                'data' => $resultsWithDetails,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Relatório de saídas por data recuperado com sucesso',
                'data' => $paginationData,
                'periodo' => [
                    'data_inicial' => $dateFrom,
                    'data_final' => $dateTo
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório de saídas por data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Erro ao gerar relatório de saídas por data'
            ], 500);
        }
    }

    /**
     * Relatório de Entradas por Data (Agregado por Produto)
     * POST /api/relatorios/entradas-por-data/list
     * 
     * Retorna lista consolidada de entradas agrupadas por data e produto.
     * Calcula a quantidade total de cada produto que entrou em cada dia,
     * somando todas as entradas de todos os fornecedores.
     * 
     * Exemplo: Se entraram 100 dipironas do Fornecedor A e 50 do Fornecedor B no mesmo dia,
     * o relatório mostrará 150 dipironas no total.
     * 
     * Filtros opcionais:
     * - Data inicial/final (se não informado, usa data atual)
     * - Setor (unidade que recebeu)
     * - Fornecedor (para filtrar por fornecedor específico)
     * - Produto (para filtrar produto específico)
     */
    public function listEntradasPorData(Request $request)
    {
        try {
            $data = $request->all();
            
            // Validação dos filtros
            $validator = Validator::make($data, [
                'filters.date_from' => 'nullable|date',
                'filters.date_to' => 'nullable|date|after_or_equal:filters.date_from',
                'filters.setor_id' => 'nullable|exists:setores,id',
                'filters.fornecedor_id' => 'nullable|exists:fornecedores,id',
                'filters.produto_id' => 'nullable|exists:produtos,id',
                'per_page' => 'nullable|integer|min:1|max:500',
                'page' => 'nullable|integer|min:1',
            ], [
                'filters.date_to.after_or_equal' => 'A data final deve ser posterior ou igual à data inicial.',
                'filters.setor_id.exists' => 'Setor não encontrado.',
                'filters.fornecedor_id.exists' => 'Fornecedor não encontrado.',
                'filters.produto_id.exists' => 'Produto não encontrado.',
                'per_page.integer' => 'Itens por página deve ser um número inteiro.',
                'per_page.min' => 'Itens por página deve ser ao menos 1.',
                'per_page.max' => 'Itens por página não pode exceder 500.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            $filters = $data['filters'] ?? [];
            
            // Se não informar período, usa data atual
            $dateFrom = $filters['date_from'] ?? date('Y-m-d');
            $dateTo = $filters['date_to'] ?? $dateFrom;

            // Query agregada: agrupa por data e produto, soma quantidades
            $query = DB::table('itens_entrada as ie')
                ->join('entrada as e', 'ie.entrada_id', '=', 'e.id')
                ->join('produtos as p', 'ie.produto_id', '=', 'p.id')
                ->join('unidade_medida as um', 'p.unidade_medida_id', '=', 'um.id')
                ->join('grupo_produto as gp', 'p.grupo_produto_id', '=', 'gp.id')
                ->select(
                    DB::raw('DATE(e.created_at) as data'),
                    'p.id as produto_id',
                    'p.nome as produto_nome',
                    'p.codigo_simpras',
                    'p.codigo_barras',
                    'um.nome as unidade_medida',
                    'gp.nome as grupo_produto',
                    'gp.tipo as tipo_produto',
                    DB::raw('SUM(ie.quantidade) as quantidade_total'),
                    DB::raw('COUNT(DISTINCT e.id) as total_entradas'),
                    DB::raw('COUNT(DISTINCT e.fornecedor_id) as total_fornecedores')
                )
                ->whereDate('e.created_at', '>=', $dateFrom)
                ->whereDate('e.created_at', '<=', $dateTo);

            // Aplicar filtros opcionais
            if (!empty($filters['setor_id'])) {
                $query->where('e.setor_id', $filters['setor_id']);
            }

            if (!empty($filters['fornecedor_id'])) {
                $query->where('e.fornecedor_id', $filters['fornecedor_id']);
            }

            if (!empty($filters['produto_id'])) {
                $query->where('p.id', $filters['produto_id']);
            }

            // Agrupar por data e produto
            $query->groupBy(
                DB::raw('DATE(e.created_at)'),
                'p.id',
                'p.nome',
                'p.codigo_simpras',
                'p.codigo_barras',
                'um.nome',
                'gp.nome',
                'gp.tipo'
            );

            // Paginação
            $perPage = $data['per_page'] ?? 50;
            $page = $data['page'] ?? 1;
            
            // Clonar query para count (sem ORDER BY que causa erro)
            $countQuery = clone $query;
            $total = $countQuery->get()->count();
            
            // Adicionar ordenação apenas na query de resultados
            $results = $query
                ->orderByDesc(DB::raw('DATE(e.created_at)'))
                ->orderByDesc(DB::raw('SUM(ie.quantidade)'))
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            // Agrupar resultados por data
            $groupedByDate = [];
            foreach ($results as $item) {
                $data = $item->data;
                
                if (!isset($groupedByDate[$data])) {
                    $groupedByDate[$data] = [
                        'data' => $data,
                        'produtos' => [],
                        'total_produtos' => 0,
                        'quantidade_total_dia' => 0
                    ];
                }
                
                // Buscar entradas detalhadas (fornecedor, nota fiscal, setor) deste produto nesta data
                $entradasDetalhadas = DB::table('itens_entrada as ie')
                    ->join('entrada as e', 'ie.entrada_id', '=', 'e.id')
                    ->join('fornecedores as f', 'e.fornecedor_id', '=', 'f.id')
                    ->join('setores as s', 'e.setor_id', '=', 's.id')
                    ->select(
                        'e.id as entrada_id',
                        'e.nota_fiscal',
                        'f.id as fornecedor_id',
                        'f.razao_social_nome as fornecedor_nome',
                        'f.cnpj as fornecedor_cnpj',
                        's.id as setor_id',
                        's.nome as setor_nome',
                        'ie.quantidade',
                        'ie.lote',
                        'ie.data_vencimento',
                        'ie.data_fabricacao',
                        'e.created_at'
                    )
                    ->where('ie.produto_id', $item->produto_id)
                    ->whereDate('e.created_at', $data)
                    ->orderBy('e.created_at', 'desc')
                    ->get();
                
                $groupedByDate[$data]['produtos'][] = [
                    'produto' => [
                        'id' => $item->produto_id,
                        'nome' => $item->produto_nome,
                        'codigo_simpras' => $item->codigo_simpras,
                        'codigo_barras' => $item->codigo_barras,
                        'unidade_medida' => $item->unidade_medida,
                        'grupo_produto' => $item->grupo_produto,
                        'tipo' => $item->tipo_produto,
                    ],
                    'quantidade_total' => (int) $item->quantidade_total,
                    'total_entradas' => (int) $item->total_entradas,
                    'total_fornecedores' => (int) $item->total_fornecedores,
                    'entradas' => $entradasDetalhadas->map(function($entrada) {
                        return [
                            'entrada_id' => $entrada->entrada_id,
                            'nota_fiscal' => $entrada->nota_fiscal,
                            'quantidade' => (int) $entrada->quantidade,
                            'lote' => $entrada->lote,
                            'data_vencimento' => $entrada->data_vencimento,
                            'data_fabricacao' => $entrada->data_fabricacao,
                            'fornecedor' => [
                                'id' => $entrada->fornecedor_id,
                                'nome' => $entrada->fornecedor_nome,
                                'cnpj' => $entrada->fornecedor_cnpj
                            ],
                            'setor' => [
                                'id' => $entrada->setor_id,
                                'nome' => $entrada->setor_nome
                            ],
                            'created_at' => $entrada->created_at
                        ];
                    })
                ];
                
                $groupedByDate[$data]['total_produtos']++;
                $groupedByDate[$data]['quantidade_total_dia'] += (int) $item->quantidade_total;
            }
            
            // Converter para array indexado e ordenar por data
            $resultsWithDetails = array_values($groupedByDate);

            $paginationData = [
                'current_page' => $page,
                'data' => $resultsWithDetails,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Relatório de entradas por data recuperado com sucesso',
                'data' => $paginationData,
                'periodo' => [
                    'data_inicial' => $dateFrom,
                    'data_final' => $dateTo
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório de entradas por data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Erro ao gerar relatório de entradas por data'
            ], 500);
        }
    }
}

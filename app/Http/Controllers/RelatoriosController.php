<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Movimentacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
}

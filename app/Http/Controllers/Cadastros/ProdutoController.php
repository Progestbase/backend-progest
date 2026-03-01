<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\Produto;
use App\Models\GrupoProduto;
use App\Models\UnidadeMedida;
use App\Http\Requests\ProdutoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProdutoController
{
    /**
     * Listar todos os produtos
     */
    public function listAll(Request $request)
    {
        try {
            $data = $request->all();
            $filters = $data['filters'] ?? [];
            $perPage = $data['per_page'] ?? 15;

            $query = Produto::with(['grupoProduto', 'unidadeMedida']);

            // Aplicar filtros
            foreach ($filters as $condition) {
                foreach ($condition as $column => $value) {
                    if ($value !== null && $value !== '') {
                        if ($column === 'grupo_produto_id' || $column === 'unidade_medida_id' || $column === 'status') {
                            $query->where($column, $value);
                        } else {
                            $query->where($column, 'like', '%' . $value . '%');
                        }
                    }
                }
            }

            $produtos = $query
                ->select('id', 'nome', 'marca', 'codigo_simpras', 'codigo_barras', 'grupo_produto_id', 'unidade_medida_id', 'status', 'created_at', 'updated_at')
                ->orderBy('nome')
                ->paginate($perPage);

            return response()->json(['status' => true, 'data' => $produtos]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar produtos: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter dados de um produto específico
     */
    public function listData(Request $request)
    {
        try {
            $id = $request->input('id');

            if (!$id) return response()->json(['status' => false, 'message' => 'ID do produto é obrigatório'], 400);

            $produto = Produto::with(['grupoProduto', 'unidadeMedida'])->find($id);

            if (!$produto) return response()->json(['status' => false, 'message' => 'Produto não encontrado'], 404);

            return response()->json(['status' => true, 'data' => $produto]);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar produto: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Criar novo produto
     */
    public function add(ProdutoRequest $request)
    {
        try {
            $data = $request->validated()['produto'];

            $produto = new Produto();
            $produto->nome = mb_strtoupper(trim($data['nome']));
            $produto->marca = !empty($data['marca']) ? mb_strtoupper(trim($data['marca'])) : null;
            $produto->codigo_simpras = !empty($data['codigo_simpras']) ? trim($data['codigo_simpras']) : null;
            $produto->codigo_barras = !empty($data['codigo_barras']) ? trim($data['codigo_barras']) : null;
            $produto->grupo_produto_id = $data['grupo_produto_id'];
            $produto->unidade_medida_id = $data['unidade_medida_id'];
            $produto->status = $data['status'] ?? 'A';

            $produto->save();

            // Recarregar com relacionamentos
            $produto->load(['grupoProduto', 'unidadeMedida']);

            return response()->json(['status' => true, 'data' => $produto, 'message' => 'Produto cadastrado com sucesso'], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar produto: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Atualizar produto existente
     */
    public function update(ProdutoRequest $request)
    {
        try {
            $data = $request->validated()['produto'];

            $produto = Produto::find($data['id']);
            if (!$produto) return response()->json(['status' => false, 'message' => 'Produto não encontrado'], 404);

            $produto->nome = mb_strtoupper(trim($data['nome']));
            $produto->marca = !empty($data['marca']) ? mb_strtoupper(trim($data['marca'])) : null;
            $produto->codigo_simpras = !empty($data['codigo_simpras']) ? trim($data['codigo_simpras']) : null;
            $produto->codigo_barras = !empty($data['codigo_barras']) ? trim($data['codigo_barras']) : null;
            $produto->grupo_produto_id = $data['grupo_produto_id'];
            $produto->unidade_medida_id = $data['unidade_medida_id'];
            $produto->status = $data['status'] ?? $produto->status;

            $produto->save();

            // Recarregar com relacionamentos
            $produto->load(['grupoProduto', 'unidadeMedida']);

            return response()->json(['status' => true, 'data' => $produto, 'message' => 'Produto atualizado com sucesso']);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar produto: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Excluir produto
     */
    public function delete(Request $request)
    {
        try {
            $id = $request->input('id');

            if (!$id) return response()->json(['status' => false, 'message' => 'ID do produto é obrigatório'], 400);

            $produto = Produto::find($id);
            if (!$produto) return response()->json(['status' => false, 'message' => 'Produto não encontrado'], 404);

            $references = [];
            if ($produto->itensEntrada()->count() > 0) $references[] = 'Entradas';
            if ($produto->itensMovimentacao()->count() > 0) $references[] = 'Movimentações';

            // Dispara erro 422 para o nosso Interceptor apanhar e listar as referências!
            if (!empty($references)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Não é possível excluir este produto pois ele possui registros associados.',
                    'references' => $references
                ], 422);
            }

            $produto->delete();

            return response()->json(['status' => true, 'message' => 'Produto excluído com sucesso']);
        } catch (\Throwable $e) {
            Log::error('Erro ao excluir produto: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Alterar status do produto (ativar/inativar)
     */
    public function toggleStatus(Request $request)
    {
        try {
            $id = $request->input('id');

            if (!$id) return response()->json(['status' => false, 'message' => 'ID do produto é obrigatório'], 400);

            $produto = Produto::find($id);
            if (!$produto) return response()->json(['status' => false, 'message' => 'Produto não encontrado'], 404);

            $produto->status = $produto->status === 'A' ? 'I' : 'A';
            $produto->save();

            // Recarregar com relacionamentos
            $produto->load(['grupoProduto', 'unidadeMedida']);

            $statusText = $produto->status === 'A' ? 'ativado' : 'inativado';

            return response()->json(['status' => true, 'data' => $produto, 'message' => "Produto {$statusText} com sucesso"]);
        } catch (\Throwable $e) {
            Log::error('Erro ao alterar status do produto: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Obter dados auxiliares para o formulário (grupos e unidades)
     */
    public function getDadosAuxiliares(Request $request)
    {
        try {
            $grupos = GrupoProduto::where('status', 'A')->select('id', 'nome')->orderBy('nome')->get();
            $unidades = UnidadeMedida::where('status', 'A')->select('id', 'nome', 'sigla')->orderBy('nome')->get();

            return response()->json(['status' => true, 'data' => ['grupos' => $grupos, 'unidades' => $unidades]]);
        } catch (\Throwable $e) {
            Log::error('Erro ao buscar dados auxiliares: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }

    /**
     * Listar produtos filtrados por tipo (Medicamento/Material).
     * Usado no formulário de movimentações para filtrar produtos pelo tipo do setor fornecedor.
     */
    public function listByTipo(Request $request)
    {
        try {
            $tipo = $request->input('tipo');

            if (!$tipo) return response()->json(['status' => false, 'message' => 'Tipo é obrigatório'], 400);

            $produtos = Produto::with(['grupoProduto', 'unidadeMedida'])
                ->whereHas('grupoProduto', function ($query) use ($tipo) {
                    $query->where('tipo', $tipo);
                })
                ->where('status', 'A')
                ->select('id', 'nome', 'marca', 'codigo_simpras', 'grupo_produto_id', 'unidade_medida_id')
                ->orderBy('nome')
                ->get();

            return response()->json(['status' => true, 'data' => $produtos]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar produtos por tipo: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno do servidor'], 500);
        }
    }
}
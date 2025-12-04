<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\Produto;
use App\Models\GrupoProduto;
use App\Models\UnidadeMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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
                        if ($column === 'grupo_produto_id' || $column === 'unidade_medida_id') {
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

            return response()->json([
                'status' => true,
                'data' => $produtos
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar produtos: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obter dados de um produto específico
     */
    public function listData(Request $request)
    {
        try {
            $data = $request->all();
            $id = $data['id'];

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do produto é obrigatório'
                ], 400);
            }

            $produto = Produto::with(['grupoProduto', 'unidadeMedida'])->find($id);

            if (!$produto) {
                return response()->json([
                    'status' => false,
                    'message' => 'Produto não encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $produto
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar produto: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Criar novo produto
     */
    public function add(Request $request)
    {
        try {
            $data = $request->all();
            $produtoData = $data['produto'] ?? $data;

            $validator = Validator::make($produtoData, [
                'nome' => 'required|string|max:255',
                'marca' => 'nullable|string|max:255',
                'codigo_simpras' => 'nullable|string|max:255|unique:produtos,codigo_simpras',
                'codigo_barras' => 'nullable|string|max:255|unique:produtos,codigo_barras',
                'grupo_produto_id' => 'required|exists:grupo_produto,id',
                'unidade_medida_id' => 'required|exists:unidade_medida,id',
                'status' => 'in:A,I'
            ], [
                'nome.required' => 'O nome do produto é obrigatório',
                'nome.max' => 'O nome não pode ter mais de 255 caracteres',
                'marca.max' => 'A marca não pode ter mais de 255 caracteres',
                'codigo_simpras.unique' => 'Este código SIMPRAS já está cadastrado',
                'codigo_barras.unique' => 'Este código de barras já está cadastrado',
                'grupo_produto_id.required' => 'O grupo do produto é obrigatório',
                'grupo_produto_id.exists' => 'Grupo de produto não encontrado',
                'unidade_medida_id.required' => 'A unidade de medida é obrigatória',
                'unidade_medida_id.exists' => 'Unidade de medida não encontrada',
                'status.in' => 'Status deve ser A (Ativo) ou I (Inativo)'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            // Criar produto
            $produto = new Produto();
            $produto->nome = mb_strtoupper(trim($produtoData['nome']));
            $produto->marca = !empty($produtoData['marca']) ? mb_strtoupper(trim($produtoData['marca'])) : null;
            $produto->codigo_simpras = !empty($produtoData['codigo_simpras']) ? trim($produtoData['codigo_simpras']) : null;
            $produto->codigo_barras = !empty($produtoData['codigo_barras']) ? trim($produtoData['codigo_barras']) : null;
            $produto->grupo_produto_id = $produtoData['grupo_produto_id'];
            $produto->unidade_medida_id = $produtoData['unidade_medida_id'];
            $produto->status = $produtoData['status'] ?? 'A';

            $produto->save();

            // Recarregar com relacionamentos
            $produto->load(['grupoProduto', 'unidadeMedida']);

            return response()->json([
                'status' => true,
                'data' => $produto,
                'message' => 'Produto cadastrado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar produto: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Atualizar produto existente
     */
    public function update(Request $request)
    {
        try {
            $data = $request->all();
            $produtoData = $data['produto'] ?? $data;
            $id = $produtoData['id'] ?? null;

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do produto é obrigatório'
                ], 400);
            }

            // Verificar se o produto existe
            $produto = Produto::find($id);
            if (!$produto) {
                return response()->json([
                    'status' => false,
                    'message' => 'Produto não encontrado'
                ], 404);
            }

            $validator = Validator::make($produtoData, [
                'nome' => 'required|string|max:255',
                'marca' => 'nullable|string|max:255',
                'codigo_simpras' => 'nullable|string|max:255|unique:produtos,codigo_simpras,' . $id,
                'codigo_barras' => 'nullable|string|max:255|unique:produtos,codigo_barras,' . $id,
                'grupo_produto_id' => 'required|exists:grupo_produto,id',
                'unidade_medida_id' => 'required|exists:unidade_medida,id',
                'status' => 'in:A,I'
            ], [
                'nome.required' => 'O nome do produto é obrigatório',
                'nome.max' => 'O nome não pode ter mais de 255 caracteres',
                'marca.max' => 'A marca não pode ter mais de 255 caracteres',
                'codigo_simpras.unique' => 'Este código SIMPRAS já está cadastrado',
                'codigo_barras.unique' => 'Este código de barras já está cadastrado',
                'grupo_produto_id.required' => 'O grupo do produto é obrigatório',
                'grupo_produto_id.exists' => 'Grupo de produto não encontrado',
                'unidade_medida_id.required' => 'A unidade de medida é obrigatória',
                'unidade_medida_id.exists' => 'Unidade de medida não encontrada',
                'status.in' => 'Status deve ser A (Ativo) ou I (Inativo)'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'validacao' => true,
                    'erros' => $validator->errors()
                ], 422);
            }

            // Atualizar produto
            $produto->nome = mb_strtoupper(trim($produtoData['nome']));
            $produto->marca = !empty($produtoData['marca']) ? mb_strtoupper(trim($produtoData['marca'])) : null;
            $produto->codigo_simpras = !empty($produtoData['codigo_simpras']) ? trim($produtoData['codigo_simpras']) : null;
            $produto->codigo_barras = !empty($produtoData['codigo_barras']) ? trim($produtoData['codigo_barras']) : null;
            $produto->grupo_produto_id = $produtoData['grupo_produto_id'];
            $produto->unidade_medida_id = $produtoData['unidade_medida_id'];
            $produto->status = $produtoData['status'] ?? 'A';

            $produto->save();

            // Recarregar com relacionamentos
            $produto->load(['grupoProduto', 'unidadeMedida']);

            return response()->json([
                'status' => true,
                'data' => $produto,
                'message' => 'Produto atualizado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar produto: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Excluir produto
     */
    public function delete(Request $request)
    {
        try {
            $data = $request->all();
            $id = $data['id'] ?? null;

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do produto é obrigatório'
                ], 400);
            }

            $produto = Produto::find($id);
            if (!$produto) {
                return response()->json([
                    'status' => false,
                    'message' => 'Produto não encontrado'
                ], 404);
            }

            // Verificar se há dependências antes de excluir
            $temItensEntrada = $produto->itensEntrada()->count() > 0;
            $temItensMovimentacao = $produto->itensMovimentacao()->count() > 0;

            if ($temItensEntrada || $temItensMovimentacao) {
                return response()->json([
                    'status' => false,
                    'message' => 'Não é possível excluir este produto pois ele possui movimentações associadas'
                ], 400);
            }

            $produto->delete();

            return response()->json([
                'status' => true,
                'message' => 'Produto excluído com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir produto: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Alterar status do produto (ativar/inativar)
     */
    public function toggleStatus(Request $request)
    {
        try {
            $data = $request->all();
            $id = $data['id'] ?? null;

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do produto é obrigatório'
                ], 400);
            }

            $produto = Produto::find($id);
            if (!$produto) {
                return response()->json([
                    'status' => false,
                    'message' => 'Produto não encontrado'
                ], 404);
            }

            $produto->status = $produto->status === 'A' ? 'I' : 'A';
            $produto->save();

            // Recarregar com relacionamentos
            $produto->load(['grupoProduto', 'unidadeMedida']);

            $statusText = $produto->status === 'A' ? 'ativado' : 'inativado';

            return response()->json([
                'status' => true,
                'data' => $produto,
                'message' => "Produto {$statusText} com sucesso"
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao alterar status do produto: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obter dados auxiliares para o formulário (grupos e unidades)
     */
    public function getDadosAuxiliares(Request $request)
    {
        try {
            $grupos = GrupoProduto::where('status', 'A')
                ->select('id', 'nome')
                ->orderBy('nome')
                ->get();

            $unidades = UnidadeMedida::where('status', 'A')
                ->select('id', 'nome', 'sigla')
                ->orderBy('nome')
                ->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'grupos' => $grupos,
                    'unidades' => $unidades
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados auxiliares: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Listar produtos filtrados por tipo (Medicamento/Material).
     * Usado no formulário de movimentações para filtrar produtos pelo tipo do setor fornecedor.
     */
    public function listByTipo(Request $request)
    {
        try {
            $data = $request->all();
            $tipo = $data['tipo'] ?? null;

            if (!$tipo) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tipo é obrigatório'
                ], 400);
            }

            // Buscar produtos cujo grupo tem o tipo especificado
            $produtos = Produto::with(['grupoProduto', 'unidadeMedida'])
                ->whereHas('grupoProduto', function ($query) use ($tipo) {
                    $query->where('tipo', $tipo);
                })
                ->where('status', 'A')
                ->select('id', 'nome', 'marca', 'codigo_simpras', 'grupo_produto_id', 'unidade_medida_id')
                ->orderBy('nome')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $produtos
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar produtos por tipo: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}

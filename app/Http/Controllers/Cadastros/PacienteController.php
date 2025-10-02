<?php

namespace App\Http\Controllers\Cadastros;

use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PacienteController
{
    /**
     * Listar todos os pacientes
     */
    public function listAll(Request $request)
    {
        try {
            $data = $request->all();
            $filters = $data['filters'] ?? [];
            $perPage = $data['per_page'] ?? 15;

            $query = Paciente::query();

            // Aplicar filtros
            foreach ($filters as $condition) {
                foreach ($condition as $column => $value) {
                    if ($value !== null && $value !== '') {
                        $query->where($column, 'like', '%' . $value . '%');
                    }
                }
            }

            $pacientes = $query
                ->orderBy('nome')
                ->paginate($perPage);

            return response()->json([
                'status' => true,
                'data' => $pacientes
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar pacientes: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Obter dados de um paciente específico
     */
    public function listData(Request $request)
    {
        try {
            $data = $request->all();
            $id = $data['id'];

            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID do paciente é obrigatório'
                ], 400);
            }

            $paciente = Paciente::find($id);

            if (!$paciente) {
                return response()->json([
                    'status' => false,
                    'message' => 'Paciente não encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $paciente
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar paciente: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Criar novo paciente
     */
    public function add(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'nome' => 'required|string|max:255',
                'cpf' => 'required|string|max:14|unique:paciente,cpf',
                'prontuario' => 'nullable|string|max:50|unique:paciente,prontuario'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors()
                ], 400);
            }

            $paciente = Paciente::create([
                'nome' => $data['nome'],
                'cpf' => $data['cpf'],
                'prontuario' => $data['prontuario'] ?? null
            ]);

            return response()->json([
                'status' => true,
                'data' => $paciente,
                'message' => 'Paciente criado com sucesso'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro ao criar paciente: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Atualizar paciente existente
     */
    public function update(Request $request)
    {
        try {
            $data = $request->all();

            $validator = Validator::make($data, [
                'id' => 'required|exists:paciente,id',
                'nome' => 'required|string|max:255',
                'cpf' => 'required|string|max:14|unique:paciente,cpf,' . $data['id'],
                'prontuario' => 'nullable|string|max:50|unique:paciente,prontuario,' . $data['id']
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Erro de validação',
                    'errors' => $validator->errors()
                ], 400);
            }

            $paciente = Paciente::find($data['id']);

            if (!$paciente) {
                return response()->json([
                    'status' => false,
                    'message' => 'Paciente não encontrado'
                ], 404);
            }

            $paciente->update([
                'nome' => $data['nome'],
                'cpf' => $data['cpf'],
                'prontuario' => $data['prontuario'] ?? null
            ]);

            return response()->json([
                'status' => true,
                'data' => $paciente,
                'message' => 'Paciente atualizado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar paciente: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }

    /**
     * Deletar paciente
     */
    public function delete(Request $request, $id)
    {
        try {
            $paciente = Paciente::find($id);

            if (!$paciente) {
                return response()->json([
                    'status' => false,
                    'message' => 'Paciente não encontrado'
                ], 404);
            }

            // Verificar se há movimentações vinculadas
            if ($paciente->movimentacoes()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Não é possível deletar paciente com movimentações vinculadas'
                ], 400);
            }

            $paciente->delete();

            return response()->json([
                'status' => true,
                'message' => 'Paciente deletado com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar paciente: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erro interno do servidor'
            ], 500);
        }
    }
}

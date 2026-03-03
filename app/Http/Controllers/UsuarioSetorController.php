<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Requests\UsuarioSetorRequest;

class UsuarioSetorController extends Controller
{
    /**
     * Vincular usuário a um setor com perfil
     */
    public function create(UsuarioSetorRequest $request)
    {
        try {
            $data = $request->validated();
            
            if (empty($data['perfil'])) {
                return response()->json(['status' => false, 'message' => 'O perfil é obrigatório para criar um vínculo.'], 400);
            }

            $usuarioId = $data['usuario_id'];
            $setorId = $data['setor_id'];
            $perfil = $data['perfil'];

            // Verificar permissão: somente admin do setor pode criar vínculo
            /** @var User $user */
            $user = Auth::user();
            $isAdmin = $user->isSuperAdmin() || $user->setores()->where('setores.id', $setorId)->wherePivot('perfil', 'admin')->exists();
            if (!$isAdmin) {
                return response()->json(['status' => false, 'message' => 'Ação permitida apenas para administradores deste setor.'], 403);
            }

            // Checar duplicidade (Para o Interceptor apanhar visualmente)
            $exists = DB::table('usuario_setor')->where('usuario_id', $usuarioId)->where('setor_id', $setorId)->exists();
            if ($exists) {
                return response()->json(['status' => false, 'message' => 'Este usuário já está vinculado a este setor.'], 422); 
            }

            $id = DB::table('usuario_setor')->insertGetId([
                'usuario_id' => $usuarioId,
                'setor_id' => $setorId,
                'perfil' => $perfil,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['status' => true, 'data' => ['id' => $id, 'usuario_id' => $usuarioId, 'setor_id' => $setorId, 'perfil' => $perfil]]);
        } catch (\Throwable $e) {
            Log::error('Erro ao criar vinculo usuario_setor: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno ao criar vínculo.'], 500);
        }
    }

    /**
     * Atualizar perfil do vínculo
     */
    public function update(UsuarioSetorRequest $request)
    {
        try {
            $data = $request->validated();

            if (empty($data['perfil'])) {
                return response()->json(['status' => false, 'message' => 'O perfil é obrigatório para atualizar.'], 400);
            }

            $usuarioId = $data['usuario_id'];
            $setorId = $data['setor_id'];
            $perfil = $data['perfil'];

            /** @var User $user */
            $user = Auth::user();
            $isAdmin = $user->isSuperAdmin() || $user->setores()->where('setores.id', $setorId)->wherePivot('perfil', 'admin')->exists();
            if (!$isAdmin) {
                return response()->json(['status' => false, 'message' => 'Ação permitida apenas para administradores deste setor.'], 403);
            }

            $row = DB::table('usuario_setor')->where('usuario_id', $usuarioId)->where('setor_id', $setorId)->first();
            if (!$row) {
                return response()->json(['status' => false, 'message' => 'Vínculo não encontrado.'], 404);
            }

            DB::table('usuario_setor')->where('id', $row->id)->update(['perfil' => $perfil, 'updated_at' => now()]);
            
            return response()->json(['status' => true, 'data' => ['usuario_id' => $usuarioId, 'setor_id' => $setorId, 'perfil' => $perfil]]);
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar vinculo usuario_setor: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno ao atualizar vínculo.'], 500);
        }
    }

    /**
     * Deletar vínculo
     */
    public function delete(UsuarioSetorRequest $request)
    {
        try {
            $data = $request->validated();
            
            $usuarioId = $data['usuario_id'];
            $setorId = $data['setor_id'];

            /** @var User $user */
            $user = Auth::user();
            $isAdmin = $user->isSuperAdmin() || $user->setores()->where('setores.id', $setorId)->wherePivot('perfil', 'admin')->exists();
            if (!$isAdmin) {
                return response()->json(['status' => false, 'message' => 'Ação permitida apenas para administradores deste setor.'], 403);
            }

            $deleted = DB::table('usuario_setor')->where('usuario_id', $usuarioId)->where('setor_id', $setorId)->delete();
            
            if ($deleted) {
                return response()->json(['status' => true, 'message' => 'Vínculo removido com sucesso.']);
            }
            
            return response()->json(['status' => false, 'message' => 'Vínculo não encontrado.'], 404);
        } catch (\Throwable $e) {
            Log::error('Erro ao deletar vinculo usuario_setor: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno ao deletar vínculo.'], 500);
        }
    }

    /**
     * Listar usuários vinculados a um setor
     */
    public function listBySetor(Request $request)
    {
        try {
            $setorId = $request->input('setor_id');
            
            if (!$setorId) {
                return response()->json(['status' => false, 'message' => 'ID do setor é obrigatório.'], 400);
            }

            $rows = DB::table('usuario_setor')
                ->join('users', 'usuario_setor.usuario_id', '=', 'users.id')
                ->where('usuario_setor.setor_id', $setorId)
                ->select('users.id', 'users.name', 'users.email', 'usuario_setor.perfil')
                ->get();

            return response()->json(['status' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar usuarios por setor: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro interno ao carregar a lista.'], 500);
        }
    }
}
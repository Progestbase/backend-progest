<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UsuarioSetorController extends Controller
{
    /**
     * Vincular usuário a um setor com perfil
     * payload: { usuario_id, setor_id, perfil }
     */
    public function create(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'usuario_id' => 'required|integer|exists:users,id',
            'setor_id' => 'required|integer|exists:setores,id',
            'perfil' => 'required|string|in:admin,almoxarife,solicitante',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'validacao' => true, 'erros' => $validator->errors()], 422);
        }

        $usuarioId = $data['usuario_id'];
        $setorId = $data['setor_id'];
        $perfil = $data['perfil'];

        // Verificar permissão: somente admin do setor pode criar vínculo
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isSuperAdmin() || $user->setores()->where('setores.id', $setorId)->wherePivot('perfil', 'admin')->exists();
        if (!$isAdmin) {
            return response()->json(['status' => false, 'message' => 'Ação permitida apenas para admin do setor.'], 403);
        }

        // Checar duplicidade
        $exists = DB::table('usuario_setor')->where('usuario_id', $usuarioId)->where('setor_id', $setorId)->exists();
        if ($exists) {
            return response()->json(['status' => false, 'message' => 'Usuário já vinculado a este setor.'], 409);
        }

        try {
            $id = DB::table('usuario_setor')->insertGetId([
                'usuario_id' => $usuarioId,
                'setor_id' => $setorId,
                'perfil' => $perfil,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return response()->json(['status' => true, 'data' => ['id' => $id, 'usuario_id' => $usuarioId, 'setor_id' => $setorId, 'perfil' => $perfil]]);
        } catch (\Exception $e) {
            Log::error('Erro ao criar vinculo usuario_setor: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro ao criar vínculo.'], 500);
        }
    }

    /**
     * Atualizar perfil do vínculo
     * payload: { usuario_id, setor_id, perfil }
     */
    public function update(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'usuario_id' => 'required|integer|exists:users,id',
            'setor_id' => 'required|integer|exists:setores,id',
            'perfil' => 'required|string|in:admin,almoxarife,solicitante',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'validacao' => true, 'erros' => $validator->errors()], 422);
        }

        $usuarioId = $data['usuario_id'];
        $setorId = $data['setor_id'];
        $perfil = $data['perfil'];

        // Permissão: somente admin do setor
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isSuperAdmin() || $user->setores()->where('setores.id', $setorId)->wherePivot('perfil', 'admin')->exists();
        if (!$isAdmin) {
            return response()->json(['status' => false, 'message' => 'Ação permitida apenas para admin do setor.'], 403);
        }

        $row = DB::table('usuario_setor')->where('usuario_id', $usuarioId)->where('setor_id', $setorId)->first();
        if (!$row) {
            return response()->json(['status' => false, 'message' => 'Vínculo não encontrado.'], 404);
        }

        try {
            DB::table('usuario_setor')->where('id', $row->id)->update(['perfil' => $perfil, 'updated_at' => now()]);
            return response()->json(['status' => true, 'data' => ['usuario_id' => $usuarioId, 'setor_id' => $setorId, 'perfil' => $perfil]]);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar vinculo usuario_setor: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro ao atualizar vínculo.'], 500);
        }
    }

    /**
     * Deletar vínculo
     * payload: { usuario_id, setor_id }
     */
    public function delete(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'usuario_id' => 'required|integer|exists:users,id',
            'setor_id' => 'required|integer|exists:setores,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'validacao' => true, 'erros' => $validator->errors()], 422);
        }

        $usuarioId = $data['usuario_id'];
        $setorId = $data['setor_id'];

        // Permissão: somente admin do setor
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isSuperAdmin() || $user->setores()->where('setores.id', $setorId)->wherePivot('perfil', 'admin')->exists();
        if (!$isAdmin) {
            return response()->json(['status' => false, 'message' => 'Ação permitida apenas para admin do setor.'], 403);
        }

        $deleted = DB::table('usuario_setor')->where('usuario_id', $usuarioId)->where('setor_id', $setorId)->delete();
        if ($deleted) {
            return response()->json(['status' => true, 'message' => 'Vínculo removido com sucesso.']);
        }
        return response()->json(['status' => false, 'message' => 'Vínculo não encontrado.'], 404);
    }

    /**
     * Listar usuários vinculados a um setor
     * payload: { setor_id }
     */
    public function listBySetor(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'setor_id' => 'required|integer|exists:setores,id',
            'paginate' => 'sometimes|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'validacao' => true, 'erros' => $validator->errors()], 422);
        }

        $setorId = $data['setor_id'];
        $rows = DB::table('usuario_setor')
            ->join('users', 'usuario_setor.usuario_id', '=', 'users.id')
            ->where('usuario_setor.setor_id', $setorId)
            ->select('users.id', 'users.name', 'users.email', 'usuario_setor.perfil')
            ->get();

        return response()->json(['status' => true, 'data' => $rows]);
    }
}

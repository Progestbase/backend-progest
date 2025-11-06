<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\TipoVinculo;
use App\Models\Setores;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response(['message' => ['Credenciais inválidas.']], 404);
        }
        $token = $user->createToken('my-app-token')->plainTextToken;
        return response(['user' => $user, 'token' => $token], 201);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $token = $user->createToken('AppName')->plainTextToken;
        return response()->json(['message' => 'Usuário registrado com sucesso!', 'token' => $token], 201);
    }

    public function logout(Request $request)
    {
        Auth::user()->tokens->each(fn($token) => $token->delete());
        return response()->json(['message' => 'Logout realizado com sucesso!']);
    }

    public function add(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data['user'], [
            'status' => 'required|string|max:1',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:4',
            'cpf' => 'required|string|max:14|unique:users',

            // 'unidade' => 'required|exists:Setores,id'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            $missingFields = [];
            foreach ($errors as $field => $messages) {
                if (in_array('The ' . $field . ' field is required.', $messages) || in_array('O campo ' . $field . ' é obrigatório.', $messages)) {
                    $missingFields[] = $field;
                }
            }
            if (!empty($missingFields)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Preencha todos os campos obrigatórios para continuar.',
                    'missing_fields' => $missingFields,
                    'errors' => $errors
                ], 422);
            }
            return response()->json([
                'status' => false,
                'message' => 'Erro de validação.',
                'errors' => $errors
            ], 422);
        }

        $user = new User;
        $user->status = $data['user']['status'] ?? 'A';
        $user->name = mb_strtoupper($data['user']['name']);
        $user->email = mb_strtolower($data['user']['email']);
        $user->telefone = isset($data['user']['telefone']) ? preg_replace('/\D/', '', $data['user']['telefone']) : null;
        $user->data_nascimento = $data['user']['data_nascimento'] ?? null;
        $user->cpf = preg_replace('/\D/', '', $data['user']['cpf']);
        $user->status = $data['user']['status'];

        $user->tipo_vinculo = $data['user']['tipo_vinculo'] ?? null;
        $user->password = bcrypt($data['user']['password']);
        DB::beginTransaction();
        try {
            $user->save();
            $incoming = $data['Setores_ids'] ?? $data['Setores'] ?? [];
            if (empty($incoming) && isset($data['user']) && is_array($data['user'])) {
                $incoming = $data['user']['Setores_ids'] ?? $data['user']['Setores'] ?? [];
            }
            if (is_array($incoming) && !empty($incoming)) {
                // Aceita duas formas:
                // - array de ids: [1,2,3]
                // - array de objetos: [{id:1, perfil:'admin'}, ...]
                $syncData = [];
                foreach ($incoming as $item) {
                    $id = null;
                    $perfil = null;
                    if (is_array($item)) {
                        $id = $item['id'] ?? ($item['setor_id'] ?? null);
                        $perfil = $item['perfil'] ?? null;
                    } elseif (is_object($item)) {
                        $id = $item->id ?? ($item->setor_id ?? null);
                        $perfil = $item->perfil ?? null;
                    } else {
                        $id = $item;
                    }
                    if (!is_numeric($id) || $id <= 0) continue;
                    // Default perfil quando não informado
                    $perfil = $perfil ?? 'solicitante';
                    $syncData[$id] = ['perfil' => $perfil];
                }
                if (!empty($syncData)) {
                    // Validar existência dos setores
                    $validIds = \App\Models\Setores::whereIn('id', array_keys($syncData))->pluck('id')->toArray();
                    $filtered = [];
                    foreach ($validIds as $vid) {
                        $filtered[$vid] = $syncData[$vid];
                    }
                    $user->setores()->sync($filtered);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar usuário e Setores: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro ao salvar usuário.'], 500);
        }

        $user = User::with(['Setores' => function ($q) {
            // 'polo_id' foi renomeado para 'unidade_id' — selecionar a coluna correta
            $q->select('Setores.id', 'Setores.unidade_id', 'Setores.nome', 'Setores.descricao', 'Setores.status', 'Setores.estoque', 'Setores.tipo');
        }])->find($user->id);

        return ['status' => true, 'data' => $user];
    }

    public function update(Request $request)
    {
        $data = $request->user;
        $user = User::find($data['id']);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado'], 404);
        }

        DB::beginTransaction();
        try {
            $user->update([
                'name' => mb_strtoupper($data['name']),
                'email' => mb_strtolower($data['email']),
                'telefone' => isset($data['telefone']) ? preg_replace('/\D/', '', $data['telefone']) : null,
                'data_nascimento' => $data['data_nascimento'] ?? null,
                'cpf' => preg_replace('/\D/', '', $data['cpf']),
                'status' => $data['status'],
                'tipo_vinculo' => $data['tipo_vinculo'] ?? null,
            ]);

            if (!empty($data['password'])) {
                $user->password = bcrypt($data['password']);
                $user->save();
            }

            // Normalizar incoming: aceita 'Setores_ids' (ids) ou 'Setores' (array de objetos com perfil)
            $incoming = $data['Setores_ids'] ?? $data['Setores'] ?? [];
            if (is_array($incoming) && !empty($incoming)) {
                $syncData = [];
                foreach ($incoming as $item) {
                    $id = null;
                    $perfil = null;
                    if (is_array($item)) {
                        $id = $item['id'] ?? ($item['setor_id'] ?? null);
                        $perfil = $item['perfil'] ?? null;
                    } elseif (is_object($item)) {
                        $id = $item->id ?? ($item->setor_id ?? null);
                        $perfil = $item->perfil ?? null;
                    } else {
                        $id = $item;
                    }
                    if (!is_numeric($id) || $id <= 0) continue;
                    $perfil = $perfil ?? 'solicitante';
                    $syncData[$id] = ['perfil' => $perfil];
                }
                if (!empty($syncData)) {
                    $validIds = Setores::whereIn('id', array_keys($syncData))->pluck('id')->toArray();
                    $filtered = [];
                    foreach ($validIds as $vid) {
                        $filtered[$vid] = $syncData[$vid];
                    }
                    $user->setores()->sync($filtered);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar usuário e Setores: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro ao atualizar usuário.'], 500);
        }

        $user = User::with(['Setores' => function ($q) {
            $q->select('Setores.id', 'Setores.unidade_id', 'Setores.nome', 'Setores.descricao', 'Setores.status', 'Setores.estoque', 'Setores.tipo');
        }])->find($user->id);

        return response()->json(['status' => true, 'data' => $user]);
    }

    public function listAll(Request $request)
    {
        $users = User::select(
            'id',
            'name',
            'email',
            'cpf',
            'telefone',
            'data_nascimento',
            'status',
            'tipo_vinculo'
        )->orderby('name')->get();
        return ['status' => true, 'data' => $users];
    }

    public function listData(Request $request)
    {
        $user = User::with(['Setores' => function ($q) {
            $q->select('Setores.id', 'Setores.unidade_id', 'Setores.nome', 'Setores.descricao', 'Setores.status', 'Setores.estoque', 'Setores.tipo');
        }])->find($request->id);

        if (!$user) {
            return ['status' => false, 'message' => 'Usuário não encontrado'];
        }

        $tipoVinculo = $user->tipo_vinculo ? TipoVinculo::find($user->tipo_vinculo) : null;

        return [
            'status' => true,
            'data' => $user,
            'tipo_vinculo' => $tipoVinculo,
            'Setores' => $user->Setores,
        ];
    }

    public function delete($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado.'], 404);
        }
        if ($user->email === 'admin@admin.com') {
            return response()->json(['status' => false, 'message' => 'O usuário Admin não pode ser excluído.'], 403);
        }
        $user->delete();
        return response()->json(['status' => true, 'message' => 'Usuário excluído com sucesso.']);
    }
}

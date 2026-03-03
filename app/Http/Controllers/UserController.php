<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TipoVinculo;
use App\Models\Setores;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UserRequest;

class UserController extends Controller
{
    public function add(UserRequest $request)
    {
        // Pega os dados validados estritos
        $validatedData = $request->validated()['user'];
        // Pega tudo (para acessar os arrays de Setores que podem vir fora do objeto 'user')
        $allData = $request->all(); 

        DB::beginTransaction();
        try {
            $user = new User;
            $user->status = $validatedData['status'] ?? 'A';
            $user->name = mb_strtoupper($validatedData['name']);
            $user->email = mb_strtolower($validatedData['email']);
            $user->telefone = isset($validatedData['telefone']) ? preg_replace('/\D/', '', $validatedData['telefone']) : null;
            $user->data_nascimento = $validatedData['data_nascimento'] ?? null;
            $user->cpf = preg_replace('/\D/', '', $validatedData['cpf']);
            $user->tipo_vinculo = $validatedData['tipo_vinculo'] ?? null;
            $user->password = Hash::make($validatedData['password']);
            
            $user->save();

            // Lógica original de sincronização de Setores e Perfis
            $this->syncSetores($user, $allData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao salvar usuário e Setores: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Erro ao salvar usuário.'], 500);
        }

        // Retorna o usuário com os relacionamentos, igual ao código original
        $user = User::with(['Setores' => function ($q) {
            $q->select('Setores.id', 'Setores.unidade_id', 'Setores.nome', 'Setores.descricao', 'Setores.status', 'Setores.estoque', 'Setores.tipo');
        }])->find($user->id);

        return response()->json(['status' => true, 'data' => $user]);
    }

    public function update(UserRequest $request)
    {   
        $validatedData = $request->validated()['user'];
        $allData = $request->all();
        $id = $validatedData['id'] ?? null; // ID precisa vir na requisição

        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado.'], 404);
        }

        DB::beginTransaction();
        try {
            $user->name = mb_strtoupper($validatedData['name']);
            $user->email = mb_strtolower($validatedData['email']);
            $user->telefone = isset($validatedData['telefone']) ? preg_replace('/\D/', '', $validatedData['telefone']) : null;
            $user->data_nascimento = $validatedData['data_nascimento'] ?? null;
            $user->cpf = preg_replace('/\D/', '', $validatedData['cpf']);
            $user->status = $validatedData['status'] ?? $user->status;
            $user->tipo_vinculo = $validatedData['tipo_vinculo'] ?? null;

            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
            }
            
            $user->save();

            // Sincroniza Setores na edição também
            $this->syncSetores($user, $allData);

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
        $filters = $request->input('filters', []);

        $query = User::query();

        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                $query->where($column, $value);
            }
        }

        $users = $query->select(
            'id', 'name', 'email', 'cpf', 'telefone', 'data_nascimento', 'status', 'tipo_vinculo'
        )->orderBy('name')->get();

        return response()->json(['status' => true, 'data' => $users]);
    }

    public function listData(Request $request)
    {
        $user = User::with(['Setores' => function ($q) {
            $q->select('Setores.id', 'Setores.unidade_id', 'Setores.nome', 'Setores.descricao', 'Setores.status', 'Setores.estoque', 'Setores.tipo');
        }])->find($request->id);

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Usuário não encontrado']);
        }

        $tipoVinculo = $user->tipo_vinculo ? TipoVinculo::find($user->tipo_vinculo) : null;

        return response()->json([
            'status' => true,
            'data' => $user,
            'tipo_vinculo' => $tipoVinculo,
            'Setores' => $user->Setores,
        ]);
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

        $user->status = 'I';
        $user->save();

        return response()->json(['status' => true, 'message' => 'Usuário desativado com sucesso.']);
    }

    public function countUsers()
    {
        $userCount = User::where('status', 'A')->count();
        return response()->json(['count' => $userCount]);
    }

    /**
     * Método auxiliar privado para reaproveitar a lógica de sincronização de setores
     * que você já tinha construído perfeitamente no AuthController.
     */
    private function syncSetores(User $user, array $data)
    {
        $incoming = $data['Setores_ids'] ?? $data['Setores'] ?? [];
        if (empty($incoming) && isset($data['user']) && is_array($data['user'])) {
            $incoming = $data['user']['Setores_ids'] ?? $data['user']['Setores'] ?? [];
        }

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
    }
}
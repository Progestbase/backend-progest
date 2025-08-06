<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Unidades;
use App\Models\TiposUsuario;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => ['These credentials do not match our records.']
            ], 404);
        }

        $token = $user->createToken('my-app-token')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
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
        $token = $user->createToken('AppName')->accessToken;

        return response()->json([
            'message' => 'Usuário registrado com sucesso!',
            'token' => $token
        ], 201);
    }

    public function logout(Request $request)
    {
        Auth::user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Logout realizado com sucesso!']);
    }

    public function add(Request $request)
    {
        $data = $request->user;

        $validador = Validator::make($data, [
            'name'                  => 'required|max:255',
            'email'                 => 'required|email|max:255|unique:users',
            'password'              => 'required|min:4',
            'cpf'                   => 'required|max:14|unique:users',
            'matricula'             => 'required|unique:users',
            'status'                => 'required',
            'unidade_consumidora_id'=> 'required',
            'usuario_tipo'          => 'required',
        ]);

        $user = new User;

        if ($validador->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validador->errors(),
            ], 422);
        }

        $data_nascimento = null;
        if (!empty($data['nascimento'])) {
            $date = \DateTime::createFromFormat('d/m/Y', $data['nascimento']);
            $data_nascimento = $date ? $date->format('Y-m-d') : null;
        }

        $user = new User;
        $user->name                     = mb_strtoupper($data['name']);
        $user->email                    = mb_strtolower($data['email']);
        $user->password                 = bcrypt($data['password']);
        $user->cpf                      = preg_replace('/\D/', '', $data['cpf']);
        $user->matricula                = $data['matricula'];
        $user->status                   = $data['status'] ? $data['status'] : 'A';
        $user->usuario_tipo             = $data['usuario_tipo'];
        $user->unidade_consumidora_id   = $data['unidade_consumidora_id'];
        $user->data_nascimento          = $data_nascimento;
        $user->telefone                 = isset($data['telefone']) ? preg_replace('/\D/', '', $data['telefone']) : null;

        $user->save();

        return ['status' => true, 'data' => $user];
    }

    public function update(Request $request)
    {
        $data = $request->user;

        // Validar dados obrigatórios
        $validador = Validator::make($data, [
            'id'                    => 'required|exists:users,id',
            'name'                  => 'required|max:255',
            'email'                 => 'required|email|max:255',
            'cpf'                   => 'required|max:14',
            'matricula'             => 'required',
            'status'                => 'required',
            'unidade_consumidora_id'=> 'required',
            'usuario_tipo'          => 'required',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => $validador->errors()
            ], 422);
        }

        // Buscar usuário
        $user = User::find($data['id']);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Usuário não encontrado.'
            ], 404);
        }

        // Verificar se email já existe em outro usuário
        $existingUser = User::where('email', mb_strtolower($data['email']))
            ->where('id', '!=', $data['id'])
            ->first();
        
        if ($existingUser) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => ['email' => ['Este email já está sendo usado por outro usuário.']]
            ], 422);
        }

        // Verificar se CPF já existe em outro usuário
        $existingCpf = User::where('cpf', preg_replace('/\D/', '', $data['cpf']))
            ->where('id', '!=', $data['id'])
            ->first();
        
        if ($existingCpf) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => ['cpf' => ['Este CPF já está sendo usado por outro usuário.']]
            ], 422);
        }

        // Verificar se matrícula já existe em outro usuário
        $existingMatricula = User::where('matricula', $data['matricula'])
            ->where('id', '!=', $data['id'])
            ->first();
        
        if ($existingMatricula) {
            return response()->json([
                'status' => false,
                'validacao' => true,
                'erros' => ['matricula' => ['Esta matrícula já está sendo usada por outro usuário.']]
            ], 422);
        }

        // Processar data de nascimento
        $data_nascimento = null;
        if (!empty($data['nascimento'])) {
            $date = \DateTime::createFromFormat('d/m/Y', $data['nascimento']);
            $data_nascimento = $date ? $date->format('Y-m-d') : null;
        }

        // Atualizar dados do usuário
        $user->name = mb_strtoupper($data['name']);
        $user->email = mb_strtolower($data['email']);
        $user->cpf = preg_replace('/\D/', '', $data['cpf']);
        $user->matricula = $data['matricula'];
        $user->status = $data['status'];
        $user->unidade_consumidora_id = $data['unidade_consumidora_id'];
        $user->usuario_tipo = $data['usuario_tipo'];
        $user->data_nascimento = $data_nascimento;
        $user->telefone = isset($data['telefone']) ? preg_replace('/\D/', '', $data['telefone']) : null;

        // Atualizar senha apenas se fornecida
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Usuário atualizado com sucesso!',
            'data' => $user
        ], 200);
    }

    public function listAll(Request $request)
    {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $paginate = isset($data['paginate']) ? $data['paginate'] : 50;

        $users = $filters;

        $usersQuery = User::query();

        // Itera sobre as condições no objeto $filters
        foreach ($filters as $condition) {
            foreach ($condition as $column => $value) {
                // Aplica cada condição como cláusula where
                $usersQuery->where($column, $value);
            }
        }

        if (!isset($data['paginate'])) {
            $users = $usersQuery
                ->select(
                    'id',
                    'name',
                    'cpf',
                    'matricula',
                    'data_nascimento',
                    'status',
                    'usuario_tipo',
                    'unidade_consumidora_id'
                )
                ->orderby('name')
                ->paginate($paginate);
        } else {
            $users = $usersQuery
                ->select(
                    'id',
                    'name',
                    'cpf',
                    'matricula',
                    'data_nascimento',
                    'status',
                    'usuario_tipo',
                    'unidade_consumidora_id'
                )
                ->orderby('name')
                ->get();
        }

        return ['status' => true, 'data' => $users];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        $user = User::find($dataID);
        if (!$user) {
            return ['status' => false, 'message' => 'Usuário não encontrado'];
        }

        $unidades = Unidades::all()->makeHidden(['codigo_unidade', 'descricao', 'status', 'created_at', 'updated_at']);

        $tipos_usuario = TiposUsuario::all()->makeHidden(['descricao', 'status', 'created_at', 'updated_at']);


        DB::enableQueryLog();

        return ['status' => true, 'data' => $user, 'query' => DB::getQueryLog(), 'unidade' => $unidades, 'tipo_usuario' => $tipos_usuario];
    }

    public function delete($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Usuário não encontrado.'
            ], 404);
        }

        // Verificar se o usuário tem referências em outras tabelas
        $references = $this->checkUserReferences($id);
        
        if (!empty($references)) {
            return response()->json([
                'status' => false,
                'message' => 'Não é possível excluir este usuário pois ele possui registros relacionados no sistema.',
                'references' => $references
            ], 422);
        }

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'Usuário excluído com sucesso.'
        ], 200);
    }

    private function checkUserReferences($userId)
    {
        $references = [];

        // Obter todas as tabelas do banco de dados
        $tables = DB::select('SHOW TABLES');
        $databaseName = 'Tables_in_' . env('DB_DATABASE');
        
        foreach ($tables as $table) {
            $tableName = $table->$databaseName;
            
            // Pular tabelas do sistema
            if (in_array($tableName, ['migrations', 'failed_jobs', 'password_resets'])) {
                continue;
            }

            // Verificar se a tabela tem colunas que podem referenciar usuários
            $columns = DB::select("SHOW COLUMNS FROM {$tableName}");
            
            foreach ($columns as $column) {
                $columnName = $column->Field;
                
                // Verificar se a coluna pode referenciar um usuário
                if ($this->isUserReferenceColumn($columnName, $tableName)) {
                    $count = 0;
                    
                    if ($tableName === 'personal_access_tokens' && $columnName === 'tokenable_id') {
                        // Verificação especial para tokens
                        $count = DB::table($tableName)
                            ->where($columnName, $userId)
                            ->where('tokenable_type', 'App\\Models\\User')
                            ->count();
                    } else {
                        $count = DB::table($tableName)
                            ->where($columnName, $userId)
                            ->count();
                    }

                    if ($count > 0) {
                        $tableDisplayName = $this->getTableDisplayName($tableName);
                        $references[] = [
                            'table' => $tableDisplayName,
                            'column' => $columnName,
                            'count' => $count,
                            'message' => "Usuário possui {$count} registro(s) na tabela {$tableDisplayName} (coluna: {$columnName})"
                        ];
                    }
                }
            }
        }

        return $references;
    }

    private function isUserReferenceColumn($columnName, $tableName)
    {
        // Padrões de colunas que podem referenciar usuários
        $userReferencePatterns = [
            'user_id',
            'created_by',
            'updated_by',
            'deleted_by',
            'approved_by',
            'rejected_by',
            'assigned_to',
            'owner_id',
            'author_id',
            'modifier_id'
        ];

        // Verificar se a coluna segue algum padrão conhecido
        foreach ($userReferencePatterns as $pattern) {
            if (str_contains(strtolower($columnName), strtolower($pattern))) {
                return true;
            }
        }

        // Verificar se é uma chave estrangeira que aponta para users
        if (str_ends_with(strtolower($columnName), '_id')) {
            // Verificar se existe uma constraint de chave estrangeira
            try {
                $foreignKeys = DB::select("
                    SELECT COLUMN_NAME, REFERENCED_TABLE_NAME 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = ? 
                    AND TABLE_NAME = ? 
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ", [env('DB_DATABASE'), $tableName, $columnName]);

                foreach ($foreignKeys as $fk) {
                    if (strtolower($fk->REFERENCED_TABLE_NAME) === 'users') {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // Se não conseguir verificar foreign keys, continua
            }
        }

        return false;
    }

    private function getTableDisplayName($tableName)
    {
        $displayNames = [
            'orders' => 'Pedidos',
            'cart_items' => 'Carrinho',
            'categorias_produtos' => 'Categorias de Produtos',
            'unidades' => 'Unidades',
            'unidades_medida' => 'Unidades de Medida',
            'tipos_usuario' => 'Tipos de Usuário',
            'produtos' => 'Produtos',
            'personal_access_tokens' => 'Tokens de Acesso'
        ];

        return $displayNames[$tableName] ?? $tableName;
    }
}

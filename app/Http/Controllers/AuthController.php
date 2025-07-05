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

        $validador = Validator::make($data, [
            'name'                  => 'required|max:255',
            'email'                 => 'required|email|max:255',
            'password'              => 'required|min:4',
            'cpf'                   => 'required|max:14',
            'matricula'             => 'required|unique:users',
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

        $user = User::find($data['id']);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Usuário não encontrado.'
            ], 404);
        }

        $user->name = mb_strtoupper($data['name']);
        $user->email = mb_strtolower($data['email']);
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        $cpf = preg_replace('/\D/', '', $data['cpf']);
        $user->cpf = $cpf;
        $user->matricula = $data['matricula'];
        $user->status = $data['status'];
        $user->unidade_consumidora_id = $data['unidade_consumidora_id'];
        $user->usuario_tipo = $data['usuario_tipo'];
        $user->data_nascimento = $data['data_nascimento'] ?? null;

        $user->save();

        return ['status' => true, 'data' => $user];
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
}

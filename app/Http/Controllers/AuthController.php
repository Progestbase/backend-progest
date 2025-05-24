<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

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
            'role' => 'solicitante'
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
        $data = $request;

        $validador = Validator::make($data['user'], [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = new User;

        if ($validador->fails()) {
            return ['status' => false, 'validacao' => true, 'erros' => $validador->errors()];
        }

        $user = new User;

        $user->name         = mb_strtoupper($data['user']['name']);
        $user->email        =  mb_strtolower($data['user']['email']);
        $user->password     = bcrypt($data['user']['password']);

        $user->save();

        return ['status' => true, 'data' => $data];
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
                ->select('id', 'name', 'email', 'role')
                ->orderby('name')
                ->paginate($paginate);
        } else {
            $users = $usersQuery
                ->select('id', 'name', 'email', 'role')
                ->orderby('name')
                ->get();
        }

        return ['status' => true, 'data' => $users];
    }

    public function listData(Request $request)
    {
        $data = $request->all();
        $dataID = $data['id'];

        DB::enableQueryLog();

        $users = User::find($dataID);

        return ['status' => true, 'data' => $users, 'query' => DB::getQueryLog()];
    }
}

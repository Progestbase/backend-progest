<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Método de login
    public function login(Request $request)
    {
        $user= User::where('email', $request->email)->first();
        // print_r($data);
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

    // Método de registro
    public function register(Request $request)
    {
        // Validação dos dados de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Criação do usuário
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'solicitante'
        ]);

        // Retorna uma resposta com o token
        $token = $user->createToken('AppName')->accessToken;

        return response()->json([
            'message' => 'Usuário registrado com sucesso!',
            'token' => $token
        ], 201);
    }

    // Método de logout
    public function logout(Request $request)
    {
        Auth::user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Logout realizado com sucesso!']);
    }
}

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
}
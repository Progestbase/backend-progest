<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    function index(Request $request)
    {
        $data = $request->all();
    }

    public function countUsers()
    {
        $userCount = User::count(); // Conta o número total de usuários
        return response()->json(['count' => $userCount]);
    }
}

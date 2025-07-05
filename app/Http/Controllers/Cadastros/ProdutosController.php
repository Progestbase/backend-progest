<?php

namespace App\Http\Controllers\Cadastros;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Unidades;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use PhpParser\Builder\Class_;

Class ProdutosController
{
    public function add(Request $request) {
        $data = $request->all();
    }

    public function listAll(Request $request) {
        $data = $request->all();
        $filters = $data['filters'] ?? [];

        $produtos = $filters;
        // $produtosQuery = Produtos::query();

        return 'listando os produtos';
    }
}
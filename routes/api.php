<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

use App\Http\Controllers\Cadastros\UnidadesController;
use App\Http\Controllers\Cadastros\FornecedorController;
use App\Http\Controllers\Cadastros\UnidadeMedidaController;
use App\Http\Controllers\Cadastros\EstoqueController;
use App\Http\Controllers\Cadastros\TipoVinculoController;
use App\Http\Controllers\Cadastros\GrupoProdutoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('countUsers', [UserController::class, 'countUsers']);

    // Cadastro de Usuários
});


Route::post("login", [AuthController::class, 'login']);
Route::post("register", [AuthController::class, 'register']);
Route::post("logout", [AuthController::class, 'logout']);
Route::post("/user/add", [AuthController::class, 'add']);
Route::post("/user/update", [AuthController::class, 'update']);
Route::post('/user/list', [AuthController::class, 'listAll']);
Route::post('/user/listData',  [AuthController::class, 'listData']);
Route::post('/user/delete/{id}',  [AuthController::class, 'delete']);

Route::post('/tipoVinculo/add', [TipoVinculoController::class, 'add']);
Route::post('/tipoVinculo/update', [TipoVinculoController::class, 'update']);
Route::post('/tipoVinculo/list', [TipoVinculoController::class, 'listAll']);
Route::post('/tipoVinculo/listData', [TipoVinculoController::class, 'listData']);
Route::post('/tipoVinculo/delete/{id}', [TipoVinculoController::class, 'delete']);

// Rotas antigas de setores removidas - usar /unidades

Route::post('/unidades/add', [UnidadesController::class, 'add']);
Route::post('/unidades/update', [UnidadesController::class, 'update']);
Route::post('/unidades/list', [UnidadesController::class, 'listAll']);
Route::post('/unidades/listData', [UnidadesController::class, 'listData']);
Route::post('/unidades/delete/{id}', [UnidadesController::class, 'delete']);

// Rotas dos módulos produtos, categoriasProdutos e unidadesMedida foram removidas
// Use os novos módulos: Produto, GrupoProduto e UnidadeMedida

// Rotas para tabela nova `unidade_medida` (singular)
Route::post('/unidadeMedida/add', [UnidadeMedidaController::class, 'add']);
Route::post('/unidadeMedida/update', [UnidadeMedidaController::class, 'update']);
Route::post('/unidadeMedida/list', [UnidadeMedidaController::class, 'listAll']);
Route::post('/unidadeMedida/listData', [UnidadeMedidaController::class, 'listData']);
Route::post('/unidadeMedida/delete/{id}', [UnidadeMedidaController::class, 'delete']);

Route::post('/fornecedores/add', [FornecedorController::class, 'add']);
Route::post('/fornecedores/update', [FornecedorController::class, 'update']);
Route::post('/fornecedores/list', [FornecedorController::class, 'listAll']);
Route::post('/fornecedores/listData', [FornecedorController::class, 'listData']);

Route::post('/estoque/add', [EstoqueController::class, 'add']);
Route::post('/estoque/update', [EstoqueController::class, 'update']);
Route::post('/estoque/list', [EstoqueController::class, 'listAll']);
Route::post('/estoque/listData', [EstoqueController::class, 'listData']);
Route::post('/estoque/delete/{id}', [EstoqueController::class, 'delete']);

Route::post('/grupoProduto/add', [GrupoProdutoController::class, 'add']);
Route::post('/grupoProduto/update', [GrupoProdutoController::class, 'update']);
Route::post('/grupoProduto/list', [GrupoProdutoController::class, 'listAll']);
Route::post('/grupoProduto/listData', [GrupoProdutoController::class, 'listData']);
Route::post('/grupoProduto/delete/{id}', [GrupoProdutoController::class, 'delete']);

// teste
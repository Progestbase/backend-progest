<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Cadastros\PerfilController;
use App\Http\Controllers\Cadastros\UnidadesController;
use App\Http\Controllers\Cadastros\ProdutosController;
use App\Http\Controllers\Cadastros\CategoriasProdutosController;
use App\Http\Controllers\Cadastros\FornecedorController;
use App\Http\Controllers\Cadastros\UnidadesMedidaController;
use App\Http\Controllers\Cadastros\EstoqueController;
use App\Http\Controllers\Cadastros\TipoVinculoController;  
use App\Http\Controllers\Cadastros\SetorController;

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
    Route::apiResource('products', ProductController::class);
    Route::get('count-below-minimum-stock', [ProductController::class, 'countProductsBelowMinimumStock']);
    Route::get('count-expiring-soon', [ProductController::class, 'countProductsExpiringSoon']);
    Route::get('countUsers', [UserController::class, 'countUsers']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::get('/cart/items', [CartController::class, 'viewCart']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'removeItem']);
    Route::delete('/cart/items', [CartController::class, 'clearCart']);
    Route::put('/cart/update/{id}', [CartController::class, 'updateItemQuantity']);
    Route::apiResource('orders', OrderController::class);

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

Route::post('/perfil/add', [PerfilController::class, 'add']);
Route::post('/perfil/update', [PerfilController::class, 'update']);
Route::post('/perfil/list', [PerfilController::class, 'listAll']);
Route::post('/perfil/listData', [PerfilController::class, 'listData']);
Route::post('/perfil/delete/{id}', [PerfilController::class, 'delete']);

Route::post('/tipoVinculo/add', [TipoVinculoController::class, 'add']);
Route::post('/tipoVinculo/update', [TipoVinculoController::class, 'update']);
Route::post('/tipoVinculo/list', [TipoVinculoController::class, 'listAll']);
Route::post('/tipoVinculo/listData', [TipoVinculoController::class, 'listData']);
Route::post('/tipoVinculo/delete/{id}', [TipoVinculoController::class, 'delete']);

Route::post('/setor/add', [SetorController::class, 'add']);
Route::post('/setor/update', [SetorController::class, 'update']);
Route::post('/setor/list', [SetorController::class, 'listAll']);
Route::post('/setor/listData', [SetorController::class, 'listData']);
Route::post('/setor/delete/{id}', [SetorController::class, 'delete']);

Route::post('/unidades/add', [UnidadesController::class, 'add']);
Route::post('/unidades/update', [UnidadesController::class, 'update']);
Route::post('/unidades/list', [UnidadesController::class, 'listAll']);
Route::post('/unidades/listData', [UnidadesController::class, 'listData']);
Route::post('/unidades/delete/{id}', [UnidadesController::class, 'delete']);

Route::post('/produtos/add', [ProdutosController::class, 'add']);
Route::post('/produtos/update', [ProdutosController::class, 'update']);
Route::post('/produtos/list', [ProdutosController::class, 'listAll']);
Route::post('/produtos/listData', [ProdutosController::class, 'listData']);
Route::post('/produtos/delete/{id}', [ProdutosController::class, 'delete']);

Route::post('/categoriasProdutos/add', [CategoriasProdutosController::class, 'add']);
Route::post('/categoriasProdutos/update', [CategoriasProdutosController::class, 'update']);
Route::post('/categoriasProdutos/list', [CategoriasProdutosController::class, 'listAll']);
Route::post('/categoriasProdutos/listData', [CategoriasProdutosController::class, 'listData']);
Route::post('/categoriasProdutos/delete/{id}', [CategoriasProdutosController::class, 'delete']);

Route::post('/unidadesMedida/add', [UnidadesMedidaController::class, 'add']);
Route::post('/unidadesMedida/update', [UnidadesMedidaController::class, 'update']);
Route::post('/unidadesMedida/list', [UnidadesMedidaController::class, 'listAll']);
Route::post('/unidadesMedida/listData', [UnidadesMedidaController::class, 'listData']);
Route::post('/unidadesMedida/delete/{id}', [UnidadesMedidaController::class, 'delete']);

Route::post('/fornecedores/add', [FornecedorController::class, 'add']);
Route::post('/fornecedores/update', [FornecedorController::class, 'update']);
Route::post('/fornecedores/list', [FornecedorController::class, 'listAll']);
Route::post('/fornecedores/listData', [FornecedorController::class, 'listData']);

Route::post('/estoque/add', [EstoqueController::class, 'add']);
Route::post('/estoque/update', [EstoqueController::class, 'update']);
Route::post('/estoque/list', [EstoqueController::class, 'listAll']);
Route::post('/estoque/listData', [EstoqueController::class, 'listData']);
Route::post('/estoque/delete/{id}', [EstoqueController::class, 'delete']);

// teste
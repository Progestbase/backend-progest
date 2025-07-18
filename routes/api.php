<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Cadastros\TiposUsuarioController;
use App\Http\Controllers\Cadastros\UnidadesController;
use App\Http\Controllers\Cadastros\ProdutosController;
use App\Http\Controllers\Cadastros\CategoriasProdutosController;
use App\Http\Controllers\Cadastros\FornecedorController;
use App\Http\Controllers\Cadastros\UnidadesMedidaController;

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

Route::post('/tiposUsuario/add', [TiposUsuarioController::class, 'add']);
Route::post('/tiposUsuario/update', [TiposUsuarioController::class, 'update']);
Route::post('/tiposUsuario/list', [TiposUsuarioController::class, 'listAll']);
Route::post('/tiposUsuario/listData', [TiposUsuarioController::class, 'listData']);

Route::post('/unidades/add', [UnidadesController::class, 'add']);
Route::post('/unidades/update', [UnidadesController::class, 'update']);
Route::post('/unidades/list', [UnidadesController::class, 'listAll']);
Route::post('/unidades/listData', [UnidadesController::class, 'listData']);

Route::post('/produtos/add', [ProdutosController::class, 'add']);
Route::post('/produtos/update', [ProdutosController::class, 'update']);
Route::post('/produtos/list', [ProdutosController::class, 'listAll']);
Route::post('/produtos/listData', [ProdutosController::class, 'listData']);

Route::post('/categoriasProdutos/add', [CategoriasProdutosController::class, 'add']);
Route::post('/categoriasProdutos/update', [CategoriasProdutosController::class, 'update']);
Route::post('/categoriasProdutos/list', [CategoriasProdutosController::class, 'listAll']);
Route::post('/categoriasProdutos/listData', [CategoriasProdutosController::class, 'listData']);

Route::post('/unidadesMedida/add', [UnidadesMedidaController::class, 'add']);
Route::post('/unidadesMedida/update', [UnidadesMedidaController::class, 'update']);
Route::post('/unidadesMedida/list', [UnidadesMedidaController::class, 'listAll']);
Route::post('/unidadesMedida/listData', [UnidadesMedidaController::class, 'listData']);

Route::post('/fornecedores/add', [FornecedorController::class, 'add']);
Route::post('/fornecedores/update', [FornecedorController::class, 'update']);
Route::post('/fornecedores/list', [FornecedorController::class, 'listAll']);
Route::post('/fornecedores/listData', [FornecedorController::class, 'listData']);
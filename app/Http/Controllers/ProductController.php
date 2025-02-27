<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Busca os produtos com current_stock diferente de 0 e retorna apenas 15 por vez
        $products = Product::where('current_stock', '>', 0)
            ->orderBy('name')
            ->paginate(15);

        // Retorna os produtos como uma resposta JSON
        return response()->json($products);
    }




    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'expiration_date' => 'required|date',
            'minimum_stock' => 'required|integer',
            'current_stock' => 'required|integer',
        ]);

        return Product::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return $product;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required',
            'expiration_date' => 'required|date',
            'minimum_stock' => 'required|integer',
            'current_stock' => 'required|integer',
        ]);

        $product->update($request->all());

        return $product;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response(null, 204);
    }

    public function countProductsBelowMinimumStock()
    {
        $productsBelowMinimum = Product::whereNotNull('current_stock')
            ->whereNotNull('minimum_stock')
            ->whereColumn('current_stock', '<', 'minimum_stock')
            ->get();

        return response()->json(['count' => $productsBelowMinimum->count()]);
    }

    public function countProductsExpiringSoon()
    {
        $dateThreshold = now()->addDays(30); // Data limite para 30 dias a partir de hoje
        $count = Product::where('expiration_date', '<', $dateThreshold)->count();
        return response()->json(['count' => $count]);
    }

    public function search(Request $request)
    {
        $searchQuery = $request->input('query');

        // Normaliza e faz a pesquisa em minúsculas para garantir correspondência
        $products = Product::where('current_stock', '>', 0) // Filtra produtos com estoque disponível
            ->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($searchQuery) . "%"])
            ->get();

        return response()->json($products);
    }

}

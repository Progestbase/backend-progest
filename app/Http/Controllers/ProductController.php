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
        return Product::all();
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
}

<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function addItem(Request $request)
    {
        $validatedData = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        foreach ($validatedData['items'] as $item) {
            // Tenta encontrar o item existente no carrinho
            $cartItem = CartItem::where('user_id', auth()->id())
                ->where('product_id', $item['product_id'])
                ->first();

            if ($cartItem) {
                // Se o item já existe, soma a quantidade
                $cartItem->quantity += $item['quantity'];
                $cartItem->save();
            } else {
                // Se não existe, cria um novo registro
                CartItem::create([
                    'user_id' => auth()->id(),
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        }

        return response()->json(['message' => 'Itens adicionados ao carrinho com sucesso!'], 201);
    }


    public function removeItem($id)
    {
        try {
            $cartItem = CartItem::where('id', $id)->where('user_id', auth()->id())->first();

            if (!$cartItem) {
                return response()->json(['message' => 'Item não encontrado'], 404);
            }

            $cartItem->delete();
            return response()->json(['message' => 'Item removido do carrinho']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function viewCart()
    {
        $cartItems = CartItem::where('user_id', auth()->id())->with('product')->get();
        return response()->json($cartItems);
    }

    public function updateItemQuantity(Request $request, $id)
{
    $validatedData = $request->validate([
        'quantity' => 'required|integer|min:1',
    ]);

    try {
        $cartItem = CartItem::where('id', $id)->where('user_id', auth()->id())->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item não encontrado'], 404);
        }

        $cartItem->quantity = $validatedData['quantity'];
        $cartItem->save();

        return response()->json(['message' => 'Quantidade atualizada com sucesso', 'item' => $cartItem]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }


}

    public function clearCart()
    {
        try {
            // Deleta todos os itens do carrinho do usuário autenticado
            CartItem::where('user_id', auth()->id())->delete();

            return response()->json(['message' => 'Carrinho limpo com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items'; // Define explicitamente a tabela
    protected $primaryKey = 'id'; // Definição explícita (não necessário, mas boa prática)
    public $timestamps = true; // Garante que created_at e updated_at sejam gerenciados pelo Laravel

    protected $fillable = ['user_id', 'product_id', 'quantity'];

    protected $casts = [
        'user_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

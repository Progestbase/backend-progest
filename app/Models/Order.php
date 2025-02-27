<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'justification',
        'password', // Código de liberação do pedido
        'status',
        'order_date',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    // Relacionamento com User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento com OrderItem (itens do pedido)
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Método para gerar um código aleatório de 6 dígitos
    public static function generatePassword()
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    }
}

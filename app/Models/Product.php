<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 
        'expiration_date', 
        'minimum_stock', 
        'current_stock', 
        'unit', 
        'brand', 
        'image_url'
    ];
}

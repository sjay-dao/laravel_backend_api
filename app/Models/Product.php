<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'sku',
        'lot_number',
        'supplier_name',
        'stock',
        'price',
        'cost_price',
        'manufactured_at',
        'expired_at',
        'category',
        'description',
        'image',
        'is_active',
    ];
}

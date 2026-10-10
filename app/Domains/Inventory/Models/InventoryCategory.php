<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    use HasFactory;

    protected $table = 'inventory_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
    ];

    public function inventoryObjects()
    {
        return $this->hasMany(
            InventoryObject::class,
            'inventory_category_id'
        );
    }
}
<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
    'code',
    'branch_id',
    'name',
    'description',
    'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

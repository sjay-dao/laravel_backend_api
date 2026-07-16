<?php

namespace App\Domains\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Inventory\Models\Warehouse;
use App\Domains\Inventory\Models\InventoryMovementItem;
use App\Domains\Inventory\Models\InventoryMovementType;
use App\Models\User;
use App\Models\Branch;


class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    protected $fillable = [
        'transaction_no',
        'movement_type_id',
        'movement_date',
        'branch_id',
        'warehouse_id',
        'reference_type',
        'reference_id',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function movementType()
    {
        return $this->belongsTo(
            InventoryMovementType::class,
            'movement_type_id'
        );
    }

    public function warehouse()
    {
        return $this->belongsTo(
            Warehouse::class,
            'warehouse_id'
        );
    }

    public function branch()
    {
        return $this->belongsTo(
            Branch::class,
            'branch_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            InventoryMovementItem::class,
            'inventory_movement_id'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
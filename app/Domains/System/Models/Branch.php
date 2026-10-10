<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address_id',
        'is_active',
    ];
    
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}

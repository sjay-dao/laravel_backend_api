<?php

namespace App\Domains\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferenceType extends Model
{
    protected $table = 'lookup_types';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function references(): HasMany
    {
        return $this->hasMany(
            Reference::class,
            'lookup_type_id'
        );
    }
}
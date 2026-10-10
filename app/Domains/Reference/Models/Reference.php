<?php

namespace App\Domains\Reference\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reference extends Model
{
    protected $table = 'lookups';

    protected $fillable = [
        'lookup_type_id',
        'code',
        'name',
        'description',
        'value',
        'color',
        'icon',
        'metadata',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(
            ReferenceType::class,
            'lookup_type_id'
        );
    }
}
<?php

namespace App\Domains\Reference\Models;

use App\Domains\System\Models\PsgcBarangay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'branches';

    protected $fillable = [
        'code',
        'name',
        'address',
        'barangay_id',
        'branch_type',
        'branch_category',
        'service_bay_count',
        'is_active',
    ];

    protected $casts = [
        'service_bay_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function barangay()
    {
        return $this->belongsTo(
            PsgcBarangay::class,
            'barangay_id'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
<?php
namespace App\Domains\Reference\Models;
use App\Domains\System\Models\PsgcBarangay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Customer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'code',
        'name',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'barangay_id',
        'tin',
        'remarks',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function barangay()
    {
        return $this->belongsTo(
            PsgcBarangay::class,
            'barangay_id'
        );
    }
}
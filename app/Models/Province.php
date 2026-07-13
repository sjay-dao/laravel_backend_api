<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Region;
class Province extends Model
{
     protected $table = 'psgc_province';
     public function region()
    {
        return $this->belongsTo(Region::class);
    }
}

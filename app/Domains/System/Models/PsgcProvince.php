<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;

class PsgcProvince extends Model
{
     protected $table = 'psgc_province';
     public function region()
    {
        return $this->belongsTo(PsgcRegion::class);
    }
}

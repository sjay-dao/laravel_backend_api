<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;

class PsgcCityMun extends Model
{
    protected $table = 'psgc_city_mun';

    public function province()
    {
        return $this->belongsTo(PsgcProvince::class);
    }
}

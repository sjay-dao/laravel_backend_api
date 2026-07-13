<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PsgcCityMun extends Model
{
    protected $table = 'psgc_city_mun';

    public function province()
    {
        return $this->belongsTo(Province::class);
    }
}

<?php

namespace App\Domains\System\Models;

use Illuminate\Database\Eloquent\Model;
use App\Domains\System\Models\PsgcCityMun;
use App\Domains\System\Models\PsgcProvince;
use App\Domains\System\Models\PsgcRegion;

class PsgcBarangay extends Model
{
    protected $table = 'psgc_barangay';

    public function cityMun()
    {
        return $this->belongsTo(
            PsgcCityMun::class,
            'city_mun_prefix',
            'prefix'
        );
    }

    public function province()
    {
        return $this->belongsTo(
            PsgcProvince::class,
            'province_prefix',
            'prefix'
        );
    }

    public function region()
    {
        return $this->belongsTo(
            PsgcRegion::class,
            'region_prefix',
            'prefix'
        );
    }
}

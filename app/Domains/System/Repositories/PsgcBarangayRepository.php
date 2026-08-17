<?php

namespace App\Domains\System\Repositories;

use App\Domains\System\Models\PsgcBarangay;

class PsgcBarangayRepository
{
    public function options(?string $search = null)
    {
        return PsgcBarangay::query()
            ->with([
                'cityMun.province',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderBy('description')
            ->limit(20)
            ->get();
    }
}
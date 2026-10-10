<?php

namespace App\Domains\System\Services;

use App\Domains\System\Models\PsgcBarangay;
use App\Domains\System\Repositories\PsgcBarangayRepository;

class PsgcBarangayService
{
    public function __construct(
        protected PsgcBarangayRepository $repository
    ) {
    }

   public function options(?string $search = null)
    {
        return PsgcBarangay::query()
            ->with([
                'cityMun.province',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('cityMun', function ($q) use ($search) {
                            $q->where('description', 'like', "%{$search}%");
                        })
                        ->orWhereHas('province', function ($q) use ($search) {
                            $q->where('description', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('description')
            ->limit(20)
            ->get();
    }
}
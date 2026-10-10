<?php

namespace App\Domains\System\Controllers;

use App\Domains\Shared\Controllers\BaseApiController;
use App\Domains\System\Resources\PsgcBarangayResource;
use App\Domains\System\Services\PsgcBarangayService;
use Illuminate\Http\Request;

class PsgcBarangayController extends BaseApiController
{
    public function __construct(
        protected PsgcBarangayService $service
    ) {
    }

    public function options(Request $request)
    {
        return $this->success(
            PsgcBarangayResource::collection(
                $this->service->options(
                    $request->search
                )
            )
        );
    }
}
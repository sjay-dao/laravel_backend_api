<?php

namespace App\Domains\Dashboard\Controllers;

use App\Domains\Dashboard\Services\BusinessOverviewService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class BusinessOverviewController extends BaseApiController
{
    public function __invoke(Request $request, BusinessOverviewService $service)
    {
        $this->authorizeAbility($request, 'dashboard.dashboards.view');

        return $this->success($service->get(), 'Business overview retrieved successfully.');
    }
}

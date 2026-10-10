<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Rms\EmployeeLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeLookupController extends Controller
{
    protected EmployeeLookupService $employeeLookupService;

    public function __construct(EmployeeLookupService $employeeLookupService)
    {
        $this->employeeLookupService = $employeeLookupService;
    }

    public function lookup(Request $request)
    {
        $employees = $this->employeeLookupService->getSystemUsersWithEmployees($request);

        return response()->json([
            'data' => $employees
        ]);
    }
}

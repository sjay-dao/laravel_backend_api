<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\Rms\EmployeeLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeLookupController extends Controller
{
    // $employeeLookupService = 
    public function lookup(Request $request)
    {
        $codes = $request->get('codes', []);

        if (empty($codes)) {
            return response()->json([
                'message' => 'No employee codes provided',
                'data' => []
            ]);
        }

        $employees = DB::connection('ems_plantilla')
            ->table('employee')
            ->select(
                'code',
                'firstname',
                'lastname',
                DB::raw("CONCAT(firstname, ' ', lastname) AS employee_name")
            )
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        $result = collect($codes)->map(function ($code) use ($employees) {
            $emp = $employees->get($code);

            return [
                'code' => $code,
                'name' => $emp ? $emp->employee_name : null,
                'exists' => $emp ? true : false,
            ];
        });

        return response()->json([
            'data' => $result
        ]);
    }
}

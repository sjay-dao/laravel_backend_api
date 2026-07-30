<?php

namespace App\Domains\Payroll\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Payroll\Requests\PayrollPreviewRequest;
use App\Domains\Payroll\Services\PayrollService;
use App\Domains\Payroll\Resources\PayrollPreviewResource;
use App\Domains\Payroll\Models\PayrollRun;
use App\Domains\Payroll\Requests\StorePayrollRunRequest;
use App\Domains\Payroll\Requests\UpdatePayrollRunRequest;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $service
    ) {
    }

    public function preview(PayrollPreviewRequest $request)
    {
        $result = $this->service->preview(
            $request->validated()
        );

        return new PayrollPreviewResource($result);
    }

    public function compute(PayrollRun $payrollRun)
    {
        return response()->json(
            $this->service->compute($payrollRun->id)
        );
    }

     public function index()
    {
        return response()->json(
            $this->service->index()
        );
    }

    public function store(StorePayrollRunRequest $request)
    {
        return response()->json(
            $this->service->create($request->validated()),
            201
        );
    }

    public function show(PayrollRun $payrollRun)
    {
        return response()->json(
            $this->service->show($payrollRun->id)
        );
    }

    public function update(
        UpdatePayrollRunRequest $request,
        PayrollRun $payrollRun
    ) {
        return response()->json(
            $this->service->update(
                $payrollRun,
                $request->validated()
            )
        );
    }

    public function destroy(PayrollRun $payrollRun)
    {
        $this->service->delete($payrollRun);

        return response()->noContent();
    }

    public function details(PayrollRun $payrollRun)
    {
        return response()->json([
            'data' => $this->service->details($payrollRun->id)
        ]);
    }
}
<?php

namespace App\Domains\Payroll\Controllers;

use App\Domains\Payroll\Models\PayrollRun;
use App\Domains\Payroll\Requests\PayrollPreviewRequest;
use App\Domains\Payroll\Requests\StorePayrollRunRequest;
use App\Domains\Payroll\Requests\UpdatePayrollRunRequest;
use App\Domains\Payroll\Resources\PayrollPreviewResource;
use App\Domains\Payroll\Services\PayrollService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class PayrollController extends BaseApiController
{
    public function __construct(
        protected PayrollService $service
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.view'
        );

        return response()->json(
            $this->service->index()
        );
    }

    public function preview(PayrollPreviewRequest $request)
    {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.view'
        );

        $result = $this->service->preview(
            $request->validated()
        );

        return new PayrollPreviewResource($result);
    }

    public function show( Request $request, PayrollRun $payrollRun) 
    {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.view'
        );

        return response()->json(
            $this->service->show($payrollRun->id)
        );
    }

    public function details(
        Request $request,
        PayrollRun $payrollRun
    ) {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.view'
        );

        return response()->json([
            'data' => $this->service->details(
                $payrollRun->id
            ),
        ]);
    }

    public function compute(
        Request $request,
        PayrollRun $payrollRun
    ) {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.process'
        );

        return response()->json(
            $this->service->compute(
                $payrollRun->id
            )
        );
    }

    public function store(
        StorePayrollRunRequest $request
    ) {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.process'
        );

        return response()->json(
            $this->service->create(
                $request->validated()
            ),
            201
        );
    }

    public function update(
        UpdatePayrollRunRequest $request,
        PayrollRun $payrollRun
    ) {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.process'
        );

        return response()->json(
            $this->service->update(
                $payrollRun,
                $request->validated()
            )
        );
    }

    public function destroy(
        Request $request,
        PayrollRun $payrollRun
    ) {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.process'
        );

        $this->service->delete(
            $payrollRun
        );

        return response()->noContent();
    }

    public function cancel(
        Request $request,
        PayrollRun $payrollRun
    ) {
        $this->authorizeAbility(
            $request,
            'payroll.payroll.process'
        );

        return response()->json(
            $this->service->cancel(
                $payrollRun
            )
        );
    }
}
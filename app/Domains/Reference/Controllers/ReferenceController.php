<?php

namespace App\Domains\Reference\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Domains\Reference\Services\ReferenceManager;

use App\Domains\Reference\Resources\ReferenceResource;

use App\Domains\Reference\Requests\StoreReferenceRequest;
use App\Domains\Reference\Requests\UpdateReferenceRequest;

class ReferenceController extends Controller
{
    public function __construct(
        protected ReferenceManager $reference
    ) {
    }

    public function index()
    {
        return ReferenceResource::collection(
            $this->reference->all()
        );
    }

    /**
     * Bootstrap all commonly-used reference data.
     */
    public function bootstrap()
    {
        return response()->json([

            'employment_statuses' =>
               
                    $this->reference->employmentStatuses(),
                

            'job_types' =>
                
                    $this->reference->jobTypes(),
                

            'contract_types' =>
                
                    $this->reference->contractTypes(),
               

            'payroll_frequencies' =>
              
                    $this->reference->payrollFrequencies(),
               

            'attendance_statuses' =>
                
                    $this->reference->attendanceStatuses(),
               

            'attendance_sources' =>
               
                    $this->reference->attendanceSources(),
                

            'schedule_types' =>
               
                    $this->reference->scheduleTypes(),
                

            'day_types' =>
                
                    $this->reference->dayTypes(),
                

        ]);
    }

    /**
     * Generic dropdown endpoint.
     */
    public function byType(string $type)
    {
        return response()->json([
            'data' => $this->reference->options($type)
        ]);
    }

    /**
     * Create a reference.
     */
    public function store(
        StoreReferenceRequest $request
    )
    {
       $record = $this->reference->store(
            $request->validated()
        );

        return new ReferenceResource($record);
    }

    /**
     * Update a reference.
     */
    public function update(
        UpdateReferenceRequest $request,
        int $reference
    )
    {
        $record = $this->reference->update(
            $reference,
            $request->validated()
        );

        return new ReferenceResource($record);
    }

    /**
     * Delete.
     */
    public function destroy(
        int $reference
    )
    {
        $this->reference
            ->delete($reference);

        return response()->json([
            'message' => 'Reference deleted.'
        ]);
    }
}
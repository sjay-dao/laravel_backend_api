<?php

namespace App\Domains\Reference\Services;

use App\Domains\Reference\Models\Reference;
use App\Domains\Reference\Services\ReferenceRecord;
use App\Domains\Reference\Objects\ReferenceTypeRecord;

class ReferenceManager
{
    public function __construct(
        protected ReferenceService $service
    ) {
    }

    /**
     * Get one reference.
     */
    public function lookup(
    string $type,
    string $code
    ): ReferenceRecord
    {
        return new ReferenceRecord(
            $this->service->find($type, $code)
        );
    }

    /**
     * Get by database id.
     */
    public function byId(
        int $id
    ): ReferenceRecord
    {
        return new ReferenceRecord(
            $this->service->findById($id)
        );
    }

    /**
     * Get lookup id.
     */
    public function id(
        string $type,
        string $code
    ): ?int
    {
        return $this->lookup($type, $code)->id();
    }

    /**
     * Get lookup code.
     */
    public function codeById(
        int $id
    ): ?string
    {
        return $this->byId($id)->code();
    }

    /**
     * Get display name.
     */
    public function name(
        string $type,
        string $code
    ): ?string
    {
        return $this->lookup($type, $code)->name();
    }

    /**
     * Get display name by id.
     */
    public function nameById(
        int $id
    ): ?string
    {
        return $this->byId($id)->name();
    }

    /**
     * Get stored value.
     */
    public function value(
        string $type,
        string $code
    )
    {
        return $this->lookup($type, $code)->value();
    }

    /**
     * Get stored value by id.
     */
    public function valueById(
        int $id
    )
    {
        return $this->byId($id)->value();
    }

    /**
     * Check if reference exists.
     */
    public function exists(
        string $type,
        string $code
    ): bool
    {
        return $this->lookup($type, $code)->exists();
    }

    /**
     * Dropdown options.
     */
    public function options(string $type)
    {
        return $this->service->options($type);
    }


    public function models(string $type)
    {
        return $this->service->models($type);
    }

    /**
     * Bootstrap multiple types.
     */
    public function bootstrap(
        array $types
    ): array
    {
        return $this->service->bootstrap($types);
    }

    /**
     * Employment Status
     */
    public function employmentStatuses()
    {
        return $this->options(
            'EMPLOYMENT_STATUS'
        );
    }

    /**
     * Job Types
     */
    public function jobTypes()
    {
        return $this->options(
            'JOB_TYPE'
        );
    }

    /**
     * Contract Types
     */
    public function contractTypes()
    {
        return $this->options(
            'CONTRACT_TYPE'
        );
    }

    /**
     * Payroll Frequencies
     */
    public function payrollFrequencies()
    {
        return $this->options(
            'PAYROLL_FREQUENCY'
        );
    }

    /**
     * Attendance Statuses
     */
    public function attendanceStatuses()
    {
        return $this->options(
            'ATTENDANCE_STATUS'
        );
    }

    /**
     * Attendance Sources
     */
    public function attendanceSources()
    {
        return $this->options(
            'ATTENDANCE_SOURCE'
        );
    }

    /**
     * Schedule Types
     */
    public function scheduleTypes()
    {
        return $this->options(
            'SCHEDULE_TYPE'
        );
    }

    /**
     * Day Types
     */
    public function dayTypes()
    {
        return $this->options(
            'DAY_TYPE'
        );
    }

    /**
     * Employment Status
     */
    public function employmentStatus(
        string $code
    ): ReferenceRecord
    {
        return $this->lookup(
            'EMPLOYMENT_STATUS',
            $code
        );
    }

    /**
     * Job Type
     */
    public function jobType(
        string $code
    ): ReferenceRecord
    {
        return $this->lookup(
            'JOB_TYPE',
            $code
        );
    }

    /**
     * Contract Type
     */
    public function contractType(
        string $code
    ): ReferenceRecord
    {
        return $this->lookup(
            'CONTRACT_TYPE',
            $code
        );
    }

    /**
     * Payroll Frequency
     */
    public function payrollFrequency(
        string $code
    ): ReferenceRecord
    {
        return $this->lookup(
            'PAYROLL_FREQUENCY',
            $code
        );
    }

    /**
     * Attendance Status
     */
    public function attendanceStatus(
        string $code
    ): ReferenceRecord
    {
        return $this->lookup(
            'ATTENDANCE_STATUS',
            $code
        );
    }

    /**
     * Attendance Source
     */
    public function attendanceSource(
        string $code
    ): ReferenceRecord
    {
        return $this->lookup(
            'ATTENDANCE_SOURCE',
            $code
        );
    }

    /**
     * Schedule Type
     */
    public function scheduleType(
        string $code
    ): ReferenceRecord
    {
        return $this->lookup(
            'SCHEDULE_TYPE',
            $code
        );
    }

    /**
     * Day Type
     */
    public function dayType(
        string $code
    ): ReferenceRecord
    {
        return $this->lookup(
            'DAY_TYPE',
            $code
        );
    }

    public function lookupType(
        string $code
    ): ReferenceTypeRecord
    {
        return new ReferenceTypeRecord(
            $this->service
                ->findTypeByCode($code)
        );
    }

    /**
     * Create a reference.
     */
    public function store(
        array $data
    )
    {
        return $this->service->create($data);
    }

    /**
     * Update a reference.
     */
    public function update(
        int $id,
        array $data
    )
    {
        $reference = $this->service->findById($id);

        if (! $reference) {
            abort(404, 'Reference not found.');
        }

        return $this->service->update(
            $reference,
            $data
        );
    }

    /**
     * Delete a reference.
     */
    public function delete(
        int $id
    ): void
    {
        $reference = $this->service->findById($id);

        if (! $reference) {
            abort(404, 'Reference not found.');
        }

        $this->service->delete($reference);
    }

    /**
     * Find by id.
     */
    public function find(
        int $id
    )
    {
        return $this->service->findById($id);
    }

    /**
     * List all references.
     */
    public function all()
    {
        return $this->service->all();
    }


}
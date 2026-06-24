<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class EmployeeLookupService
{
    protected string $connection = 'ems_plantilla';

    public function getEmployeesByCodes(array $codes)
    {
        return DB::connection($this->connection)
            ->table('employee')
            ->select(
                'code',
                DB::raw("CONCAT(firstname, ' ', lastname) AS employee_name")
            )
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');
    }

    public function enrichRecord($row, $employees)
    {
        $updatedBy = $employees->get($row->UpdatedBy);
        $resolver = $employees->get($row->assign_user);
        $requestor = $employees->get($row->requestor);

        $row->UpdatedBy = $updatedBy
            ? $row->UpdatedBy . '-' . $updatedBy->employee_name
            : $row->UpdatedBy;

        $row->resolver = $resolver
            ? $row->assign_user . '-' . $resolver->employee_name
            : $row->assign_user;

        unset($row->assign_user);

        $row->requestor = $requestor
            ? $row->requestor . '-' . $requestor->employee_name
            : $row->requestor;

        return $row;
    }

    public function extractCodes($records): array
    {
        return collect($records->items())
            ->flatMap(fn ($item) => [
                $item->UpdatedBy,
                $item->assign_user,
                $item->requestor
            ])
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}
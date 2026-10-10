<?php

namespace App\Services\Rms;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EmployeeLookupService
{
    protected string $connection = 'ems_plantilla';

    public function getEmployees(?array $codes = null)
    {
        $query = DB::connection($this->connection)
            ->table('employee')
            ->select(
                'code',
                'firstname',
                'lastname',
                DB::raw("CONCAT(firstname, ' ', lastname) AS employee_name")
            );

        if (!empty($codes)) {
            $query->whereIn('code', $codes);
        }

        return $query->get()->keyBy('code');
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

    public function getSystemUsersWithEmployees(Request $request)
    {
         $perPage = $request->input('pageSize', 20);
        // Get users + employee information
        $users = DB::connection('ems_system_access')
            ->table('system_user as su')
            ->leftJoin('ems_plantilla.employee as e', 'su.username', '=', 'e.code')
             ->leftJoin('ems_plantilla.position as p', 'e.position_id', '=', 'p.id')
             ->leftJoin('ems_plantilla.new_employee_home_branch as b', 'e.home_branch_id', '=', 'b.id')
            ->select(
                'su.id',
                'su.username',
                'su.company_id',
                'su.is_active',
                'su.is_logged_in',
                'su.is_password_changed',
                'su.last_logged_in',
                'su.last_logged_out',
                'su.last_password_change',
                'su.created_date',

                'e.code as employee_code',
                'e.position_id',
                'e.home_branch_id as branch_code',
                'e.employment_status',
                'e.status as employee_status',
                'e.corporate_email',
                'e.office_mobile',

                'p.code',
                'p.title',

                'b.old_employee_home_branch as branch_name',

                DB::raw("
                    CONCAT(
                        e.firstname,
                        IF(
                            e.middlename IS NULL OR e.middlename = '',
                            '',
                            CONCAT(' ', e.middlename)
                        ),
                        ' ',
                        e.lastname
                    ) AS full_name
                ")
            )
            ->where('su.is_active', 1)
            // ->limit(1)
            ->get();

        // Collect IDs
        $companyIds = $users->pluck('company_id')->filter()->unique()->values();
        $branchIds = $users->pluck('home_branch_id')->filter()->unique()->values();

        // Get companies from RMS server
        $companies = DB::connection('rms_database')
            ->table('tbl_companies')
            ->select('cid', 'name', 'code')
            ->whereIn('cid', $companyIds)
            ->get()
            ->keyBy('cid');

        // Get branches from RMS server
        $branches = DB::connection('rms_database')
            ->table('branches')
            ->select('id', 'branch_name', 'branch_code')
            ->whereIn('id', $branchIds)
            ->get()
            ->keyBy('id');

        // Merge results
        $users->transform(function ($user) use ($companies, $branches) {

            $company = $companies->get($user->company_id);
            // $branch = $branches->get($user->home_branch_id);

            $user->company = $company?->name;
            $user->company_code = $company?->code;

            // $user->branch_name = $branch?->branch_name;
            // $user->branch_code = $branch?->branch_code;

            return $user;
        });

        return $users;
    }
}
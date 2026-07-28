<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $this->attendanceStatuses();

        $this->employmentStatuses();

        $this->contractTypes();

        $this->scheduleTypes();

        $this->payrollFrequencies();
        
        $this->attendanceStatuses();

        $this->employmentStatuses();

        $this->contractTypes();

        $this->scheduleTypes();

        $this->payrollFrequencies();

        $this->jobTypes();
    }

    protected function typeId(string $code): int
    {
        return DB::table('lookup_types')
            ->where('code', $code)
            ->value('id');
    }

    protected function insert(int $typeId, array $rows): void
    {
        foreach ($rows as $row) {

            DB::table('lookups')->updateOrInsert(

                [
                    'lookup_type_id' => $typeId,
                    'code' => $row['code'],
                ],

                array_merge(
                    [
                        'description' => null,
                        'value' => null,
                        'color' => null,
                        'icon' => null,
                        'metadata' => null,
                        'sort_order' => 0,
                        'is_system' => true,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                    $row
                )

            );
        }
    }

    protected function attendanceStatuses(): void
    {
        $this->insert(
            $this->typeId('ATTENDANCE_STATUS'),
            [

                [
                    'code' => 'PRESENT',
                    'name' => 'Present',
                    'value' => 1,
                    'color' => '#22C55E',
                    'sort_order' => 1,
                    'metadata' => json_encode([
                        'payable' => true,
                        'counts_as_workday' => true
                    ])
                ],

                [
                    'code' => 'HALFDAY',
                    'name' => 'Half Day',
                    'value' => 0.5,
                    'color' => '#FACC15',
                    'sort_order' => 2,
                    'metadata' => json_encode([
                        'payable' => true,
                        'counts_as_workday' => true
                    ])
                ],

                [
                    'code' => 'ABSENT',
                    'name' => 'Absent',
                    'value' => 0,
                    'color' => '#EF4444',
                    'sort_order' => 3,
                    'metadata' => json_encode([
                        'payable' => false,
                        'counts_as_workday' => false
                    ])
                ],

                [
                    'code' => 'LEAVE',
                    'name' => 'Leave',
                    'value' => 1,
                    'color' => '#3B82F6',
                    'sort_order' => 4
                ],

                [
                    'code' => 'HOLIDAY',
                    'name' => 'Holiday',
                    'value' => 1,
                    'color' => '#A855F7',
                    'sort_order' => 5
                ],

                [
                    'code' => 'RESTDAY',
                    'name' => 'Rest Day',
                    'value' => 0,
                    'color' => '#9CA3AF',
                    'sort_order' => 6
                ]

            ]
        );
    }

    protected function employmentStatuses(): void
    {
        $this->insert(
            $this->typeId('EMPLOYMENT_STATUS'),
            [

                ['code'=>'PROBATIONARY','name'=>'Probationary'],
                ['code'=>'REGULAR','name'=>'Regular'],
                ['code'=>'CONTRACTUAL','name'=>'Contractual'],
                ['code'=>'PROJECT','name'=>'Project Based'],
                ['code'=>'ONCALL','name'=>'On Call'],

            ]
        );
    }

    protected function contractTypes(): void
    {
        $this->insert(
            $this->typeId('CONTRACT_TYPE'),
            [

                ['code'=>'MONTHLY','name'=>'Monthly'],
                ['code'=>'DAILY','name'=>'Daily'],
                ['code'=>'HOURLY','name'=>'Hourly'],
                ['code'=>'PIECE_RATE','name'=>'Piece Rate'],

            ]
        );
    }

    protected function scheduleTypes(): void
    {
        $this->insert(
            $this->typeId('SCHEDULE_TYPE'),
            [

                ['code'=>'FIXED','name'=>'Fixed'],
                ['code'=>'FLEXIBLE','name'=>'Flexible'],

            ]
        );
    }

    protected function payrollFrequencies(): void
    {
        $this->insert(
            $this->typeId('PAYROLL_FREQUENCY'),
            [

                ['code'=>'WEEKLY','name'=>'Weekly'],
                ['code'=>'SEMI_MONTHLY','name'=>'Semi Monthly'],
                ['code'=>'MONTHLY','name'=>'Monthly'],

            ]
        );
    }
    
    protected function attendanceSources(): void
    {
        $this->insert(
            $this->typeId('ATTENDANCE_SOURCE'),
            [

                [
                    'code' => 'MANUAL',
                    'name' => 'Manual Entry',
                    'sort_order' => 1,
                ],

                [
                    'code' => 'BIOMETRIC',
                    'name' => 'Biometric',
                    'sort_order' => 2,
                ],

                [
                    'code' => 'MOBILE',
                    'name' => 'Mobile App',
                    'sort_order' => 3,
                ],

                [
                    'code' => 'IMPORT',
                    'name' => 'Imported',
                    'sort_order' => 4,
                ],

                [
                    'code' => 'API',
                    'name' => 'API',
                    'sort_order' => 5,
                ],

            ]
        );
    }

    protected function attendanceStates(): void
    {
        $this->insert(
            $this->typeId('ATTENDANCE_STATE'),
            [

                ['code'=>'PENDING','name'=>'Pending'],

                ['code'=>'APPROVED','name'=>'Approved'],

                ['code'=>'REJECTED','name'=>'Rejected'],

            ]
        );
    }

    protected function overtimeTypes(): void
    {
        $this->insert(
            $this->typeId('OVERTIME_TYPE'),
            [

                ['code'=>'REGULAR','name'=>'Regular OT'],

                ['code'=>'RESTDAY','name'=>'Rest Day OT'],

                ['code'=>'HOLIDAY','name'=>'Holiday OT'],

                ['code'=>'SPECIAL_HOLIDAY','name'=>'Special Holiday OT'],

                ['code'=>'NIGHT_DIFF','name'=>'Night Differential'],

            ]
        );
    }

    protected function paymentMethods(): void
    {
        $this->insert(
            $this->typeId('PAYMENT_METHOD'),
            [

                ['code'=>'BANK','name'=>'Bank Transfer'],

                ['code'=>'GCASH','name'=>'GCash'],

                ['code'=>'CASH','name'=>'Cash'],

                ['code'=>'CHECK','name'=>'Check'],

            ]
        );
    }

    protected function dayTypes(): void
    {
        $this->insert(
            $this->typeId('DAY_TYPE'),
            [

                [
                    'code'=>'WORKDAY',
                    'name'=>'Work Day'
                ],

                [
                    'code'=>'RESTDAY',
                    'name'=>'Rest Day'
                ],

                [
                    'code'=>'HOLIDAY',
                    'name'=>'Holiday'
                ],

                [
                    'code'=>'SPECIAL_HOLIDAY',
                    'name'=>'Special Holiday'
                ],

            ]
        );
    }

    protected function jobTypes(): void
    {
        $this->insert(
            $this->typeId('JOB_TYPE'),
            [

                [
                    'code' => 'FULL_TIME',
                    'name' => 'Full Time',
                    'sort_order' => 1,
                ],

                [
                    'code' => 'PART_TIME',
                    'name' => 'Part Time',
                    'sort_order' => 2,
                ],

                [
                    'code' => 'PROJECT',
                    'name' => 'Project Based',
                    'sort_order' => 3,
                ],

                [
                    'code' => 'CONTRACT',
                    'name' => 'Contract',
                    'sort_order' => 4,
                ],

                [
                    'code' => 'CONSULTANT',
                    'name' => 'Consultant',
                    'sort_order' => 5,
                ],

                [
                    'code' => 'INTERN',
                    'name' => 'Intern',
                    'sort_order' => 6,
                ],

                [
                    'code' => 'FREELANCER',
                    'name' => 'Freelancer',
                    'sort_order' => 7,
                ],

            ]
        );
    }
}
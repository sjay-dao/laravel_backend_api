<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [

            [
                'code' => 'ATTENDANCE_STATUS',
                'name' => 'Attendance Status',
            ],

            [
                'code' => 'EMPLOYMENT_STATUS',
                'name' => 'Employment Status',
            ],

            [
                'code' => 'CONTRACT_TYPE',
                'name' => 'Contract Type',
            ],

            [
                'code' => 'SCHEDULE_TYPE',
                'name' => 'Schedule Type',
            ],

            [
                'code' => 'LEAVE_TYPE',
                'name' => 'Leave Type',
            ],

            [
                'code' => 'PAYROLL_FREQUENCY',
                'name' => 'Payroll Frequency',
            ],

            [
                'code' => 'ATTENDANCE_SOURCE',
                'name' => 'Attendance Source',
            ],

            [
                'code' => 'ATTENDANCE_STATE',
                'name' => 'Attendance State',
            ],

            [
                'code' => 'OVERTIME_TYPE',
                'name' => 'Overtime Type',
            ],

            [
                'code' => 'PAYMENT_METHOD',
                'name' => 'Payment Method',
            ],

            [
                'code' => 'DAY_TYPE',
                'name' => 'Day Type',
            ],
            
            [
                'code' => 'JOB_TYPE',
                'name' => 'Job Type',
            ],

        ];

        foreach ($types as $type) {

            DB::table('lookup_types')->updateOrInsert(

                [
                    'code' => $type['code']
                ],

                [
                    'name' => $type['name'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]

            );

        }

    }


}
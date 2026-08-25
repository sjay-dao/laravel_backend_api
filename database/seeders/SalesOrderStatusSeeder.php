<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class SalesOrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $lookupTypeId = DB::table('lookup_types')
            ->where('code', 'SALES_ORDER_STATUS')
            ->value('id');
        if (!$lookupTypeId) {
            $lookupTypeId = DB::table('lookup_types')->insertGetId([
                'code' => 'SALES_ORDER_STATUS',
                'name' => 'Sales Order Status',
                'description' => 'Statuses used by the Sales Order workflow.',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $statuses = [
            [
                'code' => 'DRAFT',
                'name' => 'Draft',
                'description' => 'Sales order is being prepared and can still be edited.',
                'sort_order' => 1,
                'is_system' => true,
            ],
            [
                'code' => 'CONFIRMED',
                'name' => 'Confirmed',
                'description' => 'Sales order has been confirmed and is ready for fulfillment.',
                'sort_order' => 2,
                'is_system' => true,
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelled',
                'description' => 'Sales order has been cancelled.',
                'sort_order' => 3,
                'is_system' => true,
            ],
            [
                'code' => 'CLOSED',
                'name' => 'Closed',
                'description' => 'Sales order has completed its lifecycle.',
                'sort_order' => 4,
                'is_system' => true,
            ],
        ];
        foreach ($statuses as $status) {
            DB::table('lookups')->updateOrInsert(
                [
                    'lookup_type_id' => $lookupTypeId,
                    'code' => $status['code'],
                ],
                [
                    'name' => $status['name'],
                    'description' => $status['description'],
                    'sort_order' => $status['sort_order'],
                    'is_system' => $status['is_system'],
                    'is_active' => true,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
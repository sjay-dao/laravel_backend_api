<?php

namespace Tests\Feature;

use App\Domains\Dashboard\Services\BusinessOverviewService;
use App\Domains\System\Models\User;
use App\Support\DemoDatabaseGuard;
use Database\Seeders\MicaDemoDatabaseSeeder;
use Database\Seeders\ReferenceDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class MicaDemoDatabaseReadinessTest extends TestCase
{
    private array $queriedConnections = [];

    use RefreshDatabase { refreshDatabase as protected frameworkRefreshDatabase; }

    public function refreshDatabase(): void
    {
        config(['demo.enabled' => true]);
        DB::listen(function ($query) {
            $this->queriedConnections[] = $query->connectionName;
        });
        // Prove the physical database is disposable BEFORE the test trait drops tables.
        app(DemoDatabaseGuard::class)->assertSafe();
        $this->frameworkRefreshDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MicaDemoDatabaseSeeder::class);
        $this->travelTo(now()->parse(config('demo.date').' 12:00:00'));
    }

    public function test_fresh_seed_is_small_repeatable_and_internally_consistent(): void
    {
        $counts = $this->counts();
        $this->assertLessThan(1000, array_sum($counts));
        foreach (['users' => 3, 'branches' => 1, 'warehouses' => 1, 'customers' => 4, 'suppliers' => 2, 'inventory_objects' => 11, 'inventory_lots' => 14, 'sales_orders' => 8, 'expenses' => 5] as $table => $count) {
            $this->assertSame($count, $counts[$table], $table);
        }
        $this->assertSame(0, $counts['psgc_barangay']);
        $this->assertSame(0, $counts['orders']);
        $this->assertSame(0, $counts['products']);
        $this->seed(ReferenceDatabaseSeeder::class);
        $this->seed(MicaDemoDatabaseSeeder::class);
        $this->assertSame($counts, $this->counts());
        $closed = DB::table('sales_orders')->where('status_id', $this->statusId('CLOSED'))->get();
        $this->assertCount(4, $closed);
        foreach ($closed as $order) {
            $this->assertSame((int) round($order->total_amount * 100), (int) DB::table('sales_order_payments')->where('sales_order_id', $order->id)->sum('applied_amount_cents'));
            foreach (DB::table('sales_order_items')->where('sales_order_id', $order->id)->get() as $item) {
                $this->assertEquals((float) $item->quantity, (float) DB::table('sales_order_lot_allocations')->where('sales_order_item_id', $item->id)->sum('quantity'));
            }
        }
        foreach (DB::table('inventory_lots')->get() as $lot) {
            $sold = (float) DB::table('sales_order_lot_allocations')->where('inventory_lot_id', $lot->id)->sum('quantity');
            $this->assertEquals((float) $lot->quantity_received, (float) $lot->quantity_available + $sold);
            if ($lot->ownership === 'CONSIGNMENT') {
                $this->assertNotNull($lot->supplier_id);
            }
        }
        $summary = app(BusinessOverviewService::class)->get();
        $this->assertSame(16600000, $summary['sales']['today_cents']);
        $this->assertSame(18450000, $summary['sales']['month_cents']);
        $this->assertSame(1, $summary['sales']['unpaid_count']);
        $this->assertSame(1, $summary['sales']['partially_paid_count']);
        $this->assertEquals(DB::table('inventory_lots')->sum('quantity_available'), $summary['inventory']['available_quantity']);
    }

    public function test_demo_accounts_authenticate_and_have_correct_permission_payloads(): void
    {
        foreach (['admin', 'manager', 'cashier'] as $role) {
            $user = User::where('email', "$role@mica.demo")->firstOrFail();
            $this->assertTrue(Hash::check(config('demo.password'), $user->password));
            $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => config('demo.password')])->assertOk()->json('token');
            $payload = $this->withToken($token)->getJson('/api/system/me')->assertOk();
            $codes = array_column($payload->json('permissions'), 'code');
            if ($role === 'admin') {
                $this->assertCount(DB::table('permissions')->count(), $codes);
            }
            foreach (['sales.sales.view', 'sales.sales.create', 'sales.sales.update', 'inventory.products.view'] as $code) {
                $this->assertContains($code, $codes);
            }
            $this->assertSame($role !== 'cashier', in_array('finance.expenses.create', $codes, true));
            $this->assertSame($role === 'admin', in_array('system.users.create', $codes, true));
            // Clear Sanctum's cached request guard between token identities.
            app('auth')->forgetGuards();
        }
        Sanctum::actingAs(User::where('email', 'manager@mica.demo')->firstOrFail());
        $this->getJson('/api/dashboard/business-overview')->assertOk();
        $this->getJson('/api/finance/expenses')->assertOk();
    }

    public static function saleScenarios(): array
    {
        return ['retail discount' => ['RETAIL', 1, 200000, 4500000, 4300000], 'wholesale 3' => ['WHOLESALE', 3, 0, 4250000, 12750000], 'wholesale 6' => ['WHOLESALE', 6, 0, 4050000, 24300000]];
    }

    #[DataProvider('saleScenarios')]
    public function test_cashier_can_complete_sale_without_external_connections(string $type, int $quantity, int $discount, int $price, int $total): void
    {
        Sanctum::actingAs(User::where('email', 'cashier@mica.demo')->firstOrFail());
        $this->getJson('/api/inventory/inventory-objects/summary')->assertOk();
        $this->getJson('/api/inventory/inventory-objects/options')->assertOk();
        $this->getJson('/api/inventory/units')->assertOk();
        $this->getJson('/api/inventory/warehouse')->assertOk();
        $this->getJson('/api/references/branches/options')->assertOk();
        $this->getJson('/api/references/customers/options')->assertOk();
        $product = DB::table('inventory_objects')->where('code', 'DEMO-EB-URBAN-X2')->first();
        $unitId = DB::table('units')->where('code', 'pc')->value('id');
        $order = $this->postJson('/api/sales/sales-orders', ['customer_id' => DB::table('customers')->value('id'), 'branch_id' => DB::table('branches')->value('id'), 'order_date' => config('demo.date'), 'sale_type' => $type, 'items' => [['inventory_id' => $product->id, 'unit_id' => $unitId, 'quantity' => $quantity, 'discount_cents' => $discount]]])->assertSuccessful()->json('data');
        $this->assertSame($price, $order['items'][0]['list_unit_price_cents']);
        $this->assertSame($total, $order['items'][0]['line_total_cents']);
        $id = $order['id'];
        $this->postJson("/api/sales/sales-orders/$id/confirm")->assertOk()->assertJsonPath('data.status.code', 'CONFIRMED');
        $before = DB::table('inventory_lots')->sum('quantity_available');
        $this->postJson("/api/sales/sales-orders/$id/payments", ['method' => 'CASH', 'tendered_amount_cents' => $total + 200000])->assertOk()->assertJsonPath('data.payment_summary.status', 'PAID')->assertJsonPath('data.payments.0.change_cents', 200000);
        $this->assertEquals($before, DB::table('inventory_lots')->sum('quantity_available'));
        $allocations = [];
        $remaining = $quantity;
        foreach (DB::table('inventory_lots')->where('inventory_object_id', $product->id)->orderBy('id')->get() as $lot) {
            $amount = min($remaining, (float) $lot->quantity_available);
            if ($amount > 0) {
                $allocations[] = ['sales_order_item_id' => $order['items'][0]['id'], 'inventory_lot_id' => $lot->id, 'quantity' => $amount];
                $remaining -= $amount;
            }
        }
        $request = ['warehouse_id' => DB::table('warehouses')->value('id'), 'allocations' => $allocations];
        $this->postJson("/api/sales/sales-orders/$id/complete", $request)->assertOk()->assertJsonPath('data.status.code', 'CLOSED');
        $this->assertEquals($before - $quantity, DB::table('inventory_lots')->sum('quantity_available'));
        $replay = $this->postJson("/api/sales/sales-orders/$id/complete", $request);
        $this->assertFalse($replay->isSuccessful());
        $this->assertEquals($before - $quantity, DB::table('inventory_lots')->sum('quantity_available'));
        DB::table('inventory_objects')->where('id', $product->id)->update(['retail_price_cents' => 9999900]);
        $this->getJson("/api/sales/sales-orders/$id")->assertOk()->assertJsonPath('data.items.0.list_unit_price_cents', $price)->assertJsonPath('data.payment_summary.status', 'PAID');
        $this->assertSame(9, DB::table('sales_orders')->count());
        $this->assertSame([DB::getDefaultConnection()], array_values(array_unique($this->queriedConnections)));
    }

    public function test_negative_authorization_and_demo_guard_refuse_unsafe_operations(): void
    {
        Sanctum::actingAs(User::where('email', 'cashier@mica.demo')->firstOrFail());
        $this->postJson('/api/inventory/lots', ['inventory_object_id' => DB::table('inventory_objects')->value('id'), 'warehouse_id' => DB::table('warehouses')->value('id'), 'ownership' => 'OWNED', 'quantity_received' => 1, 'settlement_cost_cents' => 100, 'received_date' => config('demo.date')])->assertForbidden();
        $this->postJson('/api/finance/expenses', ['expense_date' => config('demo.date'), 'category' => 'Supplies', 'description' => 'Unauthorized', 'amount_cents' => 100])->assertForbidden();
        $user = User::create(['name' => 'No permissions', 'email' => 'no-access@example.test', 'password' => Hash::make('test-password')]);
        Sanctum::actingAs($user);
        $orderId = DB::table('sales_orders')->value('id');
        foreach (['confirm', 'payments', 'complete'] as $action) {
            $this->postJson('/api/sales/sales-orders/'.$orderId.'/'.$action, [])->assertForbidden();
        }
        $this->getJson('/api/sales/sales-orders')->assertForbidden();
        $before = $this->counts();
        config(['demo.enabled' => false]);
        $this->artisan('demo:reset', ['--force' => true])->assertExitCode(1);
        $this->assertSame($before, $this->counts());
        config(['demo.enabled' => true]);
        $this->artisan('demo:reset')->assertExitCode(1);
        $this->assertSame($before, $this->counts());
        app()->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        app(DemoDatabaseGuard::class)->assertSafe();
    }

    private function counts(): array
    {
        $counts = [];
        foreach (array_column(Schema::getTables(DB::connection()->getDriverName() === 'sqlite' ? 'main' : DB::connection()->getDatabaseName()), 'name') as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    private function statusId(string $code): int
    {
        return (int) DB::table('lookups')->where('code', $code)->whereIn('lookup_type_id', DB::table('lookup_types')->select('id')->where('code', 'SALES_ORDER_STATUS'))->value('id');
    }
}

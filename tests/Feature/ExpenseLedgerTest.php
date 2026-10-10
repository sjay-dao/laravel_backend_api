<?php

namespace Tests\Feature;

use App\Domains\System\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class ExpenseLedgerTest extends InventoryLotReceivingApiTest
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_10_01_020000_create_expenses.php'))->up();

        $now = now();
        DB::table('permissions')->insert([
            ['id' => 3, 'module' => 'finance', 'resource' => 'expenses', 'action' => 'view', 'code' => 'finance.expenses.view', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 4, 'module' => 'finance', 'resource' => 'expenses', 'action' => 'create', 'code' => 'finance.expenses.create', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('role_permissions')->insert([
            ['role_id' => 1, 'permission_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['role_id' => 1, 'permission_id' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function test_authorized_user_can_record_and_view_an_integer_cent_expense(): void
    {
        Sanctum::actingAs(User::findOrFail(1));

        $this->postJson('/api/finance/expenses', [
            'expense_date' => now()->toDateString(),
            'category' => 'Utilities',
            'description' => 'Electric bill',
            'amount_cents' => 125050,
            'reference' => 'OR-1001',
            'notes' => 'Main branch',
        ])->assertCreated()
            ->assertJsonPath('data.amount_cents', 125050)
            ->assertJsonPath('data.created_by.name', 'Stock Clerk');

        $this->getJson('/api/finance/expenses?category=Utilities')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'OR-1001');

        $this->assertDatabaseHas('expenses', [
            'amount_cents' => 125050,
            'created_by' => 1,
        ]);
    }

    public function test_zero_and_negative_expenses_are_rejected(): void
    {
        Sanctum::actingAs(User::findOrFail(1));
        foreach ([0, -1] as $amount) {
            $this->postJson('/api/finance/expenses', [
                'expense_date' => now()->toDateString(),
                'category' => 'Other',
                'description' => 'Invalid',
                'amount_cents' => $amount,
            ])->assertUnprocessable()->assertJsonValidationErrors('amount_cents');
        }

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_user_without_expense_permissions_cannot_view_or_create_expenses(): void
    {
        Sanctum::actingAs(User::findOrFail(2));

        $this->getJson('/api/finance/expenses')->assertForbidden();
        $this->postJson('/api/finance/expenses', [
            'expense_date' => now()->toDateString(),
            'category' => 'Other',
            'description' => 'Blocked',
            'amount_cents' => 100,
        ])->assertForbidden();
    }
}

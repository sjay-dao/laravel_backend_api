<?php

namespace App\Domains\Employee\Services;

use App\Domains\Employee\Models\EmployeeTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class EmployeeTransactionService
{
    private const NEGATIVE_TYPES = ['advance', 'deduction'];
    public function __construct(private EmployeeActivityService $activity) {}
    public function create(array $data): EmployeeTransaction
    {
        return DB::transaction(function () use ($data) {
            $effect = in_array($data['type'], self::NEGATIVE_TYPES, true) ? -$data['amount'] : $data['amount'];
            $transaction = EmployeeTransaction::create($data + ['transaction_no' => 'EMP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8)), 'ledger_effect' => $effect, 'status' => 'posted', 'created_by' => Auth::id()]);
            $this->activity->record('employee_transaction.posted', $transaction, $transaction->employee_id, [], $transaction->getAttributes());
            return $transaction->load('employee');
        });
    }
    public function void(EmployeeTransaction $transaction): EmployeeTransaction
    {
        if ($transaction->status !== 'posted') { throw new RuntimeException('Only posted transactions can be voided.'); }
        return DB::transaction(function () use ($transaction) {
            $transaction->update(['status' => 'voided', 'voided_at' => now(), 'voided_by' => Auth::id()]);
            $this->activity->record('employee_transaction.voided', $transaction, $transaction->employee_id, ['status' => 'posted'], ['status' => 'voided']);
            return $transaction->fresh()->load('employee');
        });
    }
    public function ledger(int $employeeId)
    {
        $balance = 0;
        return EmployeeTransaction::query()->where('employee_id', $employeeId)->where('status', 'posted')->orderBy('transaction_date')->orderBy('id')->get()->map(function (EmployeeTransaction $transaction) use (&$balance) { $balance += (float) $transaction->ledger_effect; return ['transaction' => $transaction, 'running_balance' => number_format($balance, 2, '.', '')]; });
    }
}

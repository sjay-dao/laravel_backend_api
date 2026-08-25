<?php
namespace App\Domains\Sales\Repositories;
use App\Domains\Sales\Models\SalesOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
class SalesOrderRepository
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return SalesOrder::query()
            ->with(['customer', 'branch', 'status', 'items.inventory', 'items.unit'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['customer_id'] ?? null, fn ($query, $customerId) => $query->where('customer_id', $customerId))
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['status_id'] ?? null, fn ($query, $statusId) => $query->where('status_id', $statusId))
            ->when($filters['order_date_from'] ?? null, fn ($query, $date) => $query->whereDate('order_date', '>=', $date))
            ->when($filters['order_date_to'] ?? null, fn ($query, $date) => $query->whereDate('order_date', '<=', $date))
            ->latest('id')
            ->paginate($perPage);
    }
    public function find(int $id): SalesOrder
    {
        return SalesOrder::query()
            ->with(['customer', 'branch', 'status','items.inventory', 'items.unit'])
            ->findOrFail($id);
    }
    public function create(array $data): SalesOrder
    {
        return SalesOrder::create($data);
    }
    public function update(SalesOrder $salesOrder, array $data): SalesOrder
    {
        $salesOrder->update($data);
        return $salesOrder->refresh();
    }
    public function delete(SalesOrder $salesOrder): void
    {
        $salesOrder->delete();
    }
    public function generateOrderNumber(): string
    {
        return DB::transaction(function () {
            $date = now()->format('Ymd');
            $prefix = "SO-{$date}-";
            $lastOrder = SalesOrder::withTrashed()
                ->where('order_no', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();
            $sequence = 1;
            if ($lastOrder) {
                $lastSequence = (int) substr($lastOrder->order_no, -6);
                $sequence = $lastSequence + 1;
            }
            return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        });
    }
}
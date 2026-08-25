<?php
namespace App\Domains\Sales\Services;
use App\Domains\Sales\Models\SalesOrder;
use App\Domains\Sales\Repositories\SalesOrderRepository;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
class SalesOrderService
{
    public function __construct(
        protected SalesOrderRepository $repository
    ) {
    }
    public function create(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            unset($data['items']);
            if (empty($items)) {
                throw new InvalidArgumentException('A sales order must contain at least one item.');
            }
            $data['order_no'] = $this->repository->generateOrderNumber();
            $data['status_id'] = $this->getDraftStatusId();
            $totals = $this->calculateTotals($items);
            $data = array_merge($data, $totals);
            $salesOrder = $this->repository->create($data);
            $this->createItems($salesOrder, $items);
            return $salesOrder->load([
                'customer',
                'branch',
                'status',
                'items.inventory',
                'items.unit',
            ]);
        });
    }
    public function update(SalesOrder $salesOrder, array $data): SalesOrder
    {
        $this->ensureEditable($salesOrder);
        return DB::transaction(function () use ($salesOrder, $data) {
            $items = $data['items'] ?? null;
            unset($data['items']);
            if ($items !== null) {
                if (empty($items)) {
                    throw new InvalidArgumentException('A sales order must contain at least one item.');
                }
                $totals = $this->calculateTotals($items);
                $data = array_merge($data, $totals);
            }
            $salesOrder = $this->repository->update($salesOrder, $data);
            if ($items !== null) {
                $salesOrder->items()->delete();
                $this->createItems($salesOrder, $items);
            }
            return $salesOrder->load([
                'customer',
                'branch',
                'items.inventory',
                'items.unit',
            ]);
        });
    }
    public function confirm(SalesOrder $salesOrder): SalesOrder
    {
        $this->ensureStatus($salesOrder, 'DRAFT');
        $salesOrder->update([
            'status_id' => $this->getStatusId('CONFIRMED'),
        ]);
        return $salesOrder->refresh()->load([
            'customer',
            'branch',
            'items.inventory',
            'items.unit',
        ]);
    }
    public function cancel(SalesOrder $salesOrder): SalesOrder
    {
        if (!in_array($this->getStatusCode($salesOrder), ['DRAFT', 'CONFIRMED'], true)) {
            throw new InvalidArgumentException('This sales order cannot be cancelled.');
        }
        $salesOrder->update([
            'status_id' => $this->getStatusId('CANCELLED'),
        ]);
        return $salesOrder->refresh();
    }
    protected function createItems(SalesOrder $salesOrder, array $items): void
    {
        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $discount = (float) ($item['discount_amount'] ?? 0);
            $tax = (float) ($item['tax_amount'] ?? 0);
            $lineTotal = ($quantity * $unitPrice) - $discount + $tax;
            $salesOrder->items()->create([
                'inventory_id' => $item['inventory_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'line_total' => $lineTotal,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }
    }
    protected function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $discount = 0;
        $tax = 0;
        foreach ($items as $item) {
            $quantity = (float) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $subtotal += $quantity * $unitPrice;
            $discount += (float) ($item['discount_amount'] ?? 0);
            $tax += (float) ($item['tax_amount'] ?? 0);
        }
        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $subtotal - $discount + $tax,
        ];
    }
    protected function ensureEditable(SalesOrder $salesOrder): void
    {
        if ($this->getStatusCode($salesOrder) !== 'DRAFT') {
            throw new InvalidArgumentException('Only draft sales orders can be edited.');
        }
    }
    protected function ensureStatus(SalesOrder $salesOrder, string $expectedStatus): void
    {
        if ($this->getStatusCode($salesOrder) !== $expectedStatus) {
            throw new InvalidArgumentException(
                "Sales order must be {$expectedStatus} before this action."
            );
        }
    }
    protected function getStatusCode(SalesOrder $salesOrder): string
    {
        return $salesOrder->status?->code ?? '';
    }
    protected function getDraftStatusId(): int
    {
        return $this->getStatusId('DRAFT');
    }
    protected function getStatusId(string $code): int
    {
        $lookupTypeId = DB::table('lookup_types')
            ->where('code', 'SALES_ORDER_STATUS')
            ->value('id');
        return (int) DB::table('lookups')
            ->where('lookup_type_id', $lookupTypeId)
            ->where('code', $code)
            ->where('is_active', true)
            ->value('id');
    }
}
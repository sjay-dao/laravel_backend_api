<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items.product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:255|unique:orders,order_number',
            'supplier_name' => 'required|string|max:255',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'nullable|in:pending,received,cancelled',
            'remarks' => 'nullable|string',
            'payment_type' => 'nullable|in:cash,scheduled,external_installment',
            'payment_status' => 'nullable|in:unpaid,pending,partial,paid,overdue,cancelled',
            'payment_due_date' => 'nullable|date',
            'external_payment_reference' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($request, $validated) {
            $totalAmount = 0;

            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => $validated['order_number'],
                'supplier_name' => $validated['supplier_name'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => $validated['status'] ?? 'pending',
                'total_amount' => 0,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::where('user_id', $request->user()->id)
                    ->findOrFail($item['product_id']);

                $subtotal = $item['quantity'] * $item['unit_cost'];
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $subtotal,
                ]);

                if (($validated['status'] ?? 'pending') === 'received') {
                    $product->increment('stock', $item['quantity']);
                }
            }

            $order->update([
                'total_amount' => $totalAmount
            ]);

            return $order->load('items.product');
        });

        return response()->json([
            'message' => 'Order created successfully.',
            'order' => $order
        ], 201);
    }

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'order' => $order->load('items.product')
        ]);
    }

    public function update(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'supplier_name' => 'sometimes|required|string|max:255',
            'order_date' => 'sometimes|required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'status' => 'sometimes|required|in:pending,received,cancelled',
            'remarks' => 'nullable|string',
            'payment_type' => 'nullable|in:cash,scheduled,external_installment',
            'payment_status' => 'nullable|in:unpaid,pending,partial,paid,overdue,cancelled',
            'payment_due_date' => 'nullable|date',
            'external_payment_reference' => 'nullable|string|max:255',
        ]);

        $order->update($validated);

        return response()->json([
            'message' => 'Order updated successfully.',
            'order' => $order->load('items.product')
        ]);
    }

    public function destroy(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.'
        ]);
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:255',
        ]);

        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order' => $order,
        ]);
    }
}
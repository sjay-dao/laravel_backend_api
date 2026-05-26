<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $products = Product::all();

        if (!$user || $products->isEmpty()) {
            $this->command->warn('Need at least 1 user and 1 product.');
            return;
        }

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'PO-2026-0002',
            'supplier_name' => 'Honda Philippines',
            'order_date' => '2026-05-26',
            'expected_delivery_date' => '2026-06-05',
            'status' => 'pending',
            'total_amount' => 0,
            'remarks' => 'Bulk motorcycle inventory replenishment for branch stock.',
        ]);

        $total = 0;

        foreach ($products as $product) {
            $quantity = 2;
            $unitCost = $product->price;

            $subtotal = $quantity * $unitCost;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'subtotal' => $subtotal,
            ]);

            $total += $subtotal;
        }

        $order->update([
            'total_amount' => $total,
        ]);
    }
}
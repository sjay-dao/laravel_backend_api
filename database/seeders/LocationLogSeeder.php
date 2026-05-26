<?php

namespace Database\Seeders;

use App\Models\LocationLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class LocationLogSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $order = Order::first();

        if (!$user || !$order) {
            $this->command->warn('User or Order missing. Cannot seed location logs.');
            return;
        }

        LocationLog::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'latitude' => 14.5995123,
            'longitude' => 120.9842195,
            'status' => 'in_transit',
            'remarks' => 'Motorcycle units departed from supplier warehouse.',
            'recorded_at' => '2026-05-26 10:30:00',
        ]);
    }
}
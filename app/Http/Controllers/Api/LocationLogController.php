<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationLog;
use App\Models\Order;
use Illuminate\Http\Request;

class LocationLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = LocationLog::with('order')
            ->where('user_id', $request->user()->id)
            ->latest('recorded_at')
            ->get();

        return response()->json([
            'location_logs' => $logs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'status' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'recorded_at' => 'nullable|date',
        ]);

        $order = Order::where('user_id', $request->user()->id)
            ->findOrFail($validated['order_id']);

        $log = LocationLog::create([
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => $validated['status'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'message' => 'Location log created successfully.',
            'location_log' => $log->load('order')
        ], 201);
    }

    public function show(Request $request, LocationLog $locationLog)
    {
        if ($locationLog->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'location_log' => $locationLog->load('order')
        ]);
    }

    public function update(Request $request, LocationLog $locationLog)
    {
        if ($locationLog->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'status' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'recorded_at' => 'nullable|date',
        ]);

        $locationLog->update($validated);

        return response()->json([
            'message' => 'Location log updated successfully.',
            'location_log' => $locationLog->load('order')
        ]);
    }

    public function destroy(Request $request, LocationLog $locationLog)
    {
        if ($locationLog->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $locationLog->delete();

        return response()->json([
            'message' => 'Location log deleted successfully.'
        ]);
    }

    public function orderLogs(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $logs = LocationLog::where('order_id', $order->id)
            ->where('user_id', $request->user()->id)
            ->oldest('recorded_at')
            ->get();

        return response()->json([
            'order' => $order,
            'location_logs' => $logs
        ]);
    }

    public function latestByOrder(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $latestLog = LocationLog::where('order_id', $order->id)
            ->where('user_id', $request->user()->id)
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'order' => $order,
            'latest_location' => $latestLog
        ]);
    }
}
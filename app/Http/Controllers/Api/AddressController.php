<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    /**
     * Display a listing of addresses.
     */
    public function index(): JsonResponse
    {
        $addresses = Address::with([
            'user',
            'barangay'
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    /**
     * Store a newly created address.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'label' => 'required|string|max:255',

            'contact_person' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',

            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',

            'barangay_id' => 'nullable|exists:psgc_barangays,id',

            'postal_code' => 'nullable|string|max:20',

            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'is_default' => 'nullable|boolean',
        ]);

        if (!empty($validated['is_default']) && !empty($validated['user_id'])) {
            Address::where('user_id', $validated['user_id'])
                ->update(['is_default' => false]);
        }

        $address = Address::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully.',
            'data' => $address->load([
                'user',
                'barangay'
            ])
        ], 201);
    }

    /**
     * Display the specified address.
     */
    public function show(Address $address): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $address->load([
                'user',
                'barangay'
            ])
        ]);
    }

    /**
     * Update the specified address.
     */
    public function update(Request $request, Address $address): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'label' => 'sometimes|required|string|max:255',

            'contact_person' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',

            'address_line_1' => 'sometimes|required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',

            'barangay_id' => 'nullable|exists:psgc_barangays,id',

            'postal_code' => 'nullable|string|max:20',

            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'is_default' => 'nullable|boolean',
        ]);

        if (
            isset($validated['is_default']) &&
            $validated['is_default'] &&
            !empty($validated['user_id'])
        ) {
            Address::where('user_id', $validated['user_id'])
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data' => $address->fresh()->load([
                'user',
                'barangay'
            ])
        ]);
    }

    /**
     * Remove the specified address.
     */
    public function destroy(Address $address): JsonResponse
    {
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.'
        ]);
    }
}
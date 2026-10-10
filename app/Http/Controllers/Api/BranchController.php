<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domains\System\Models\Branch;
use App\Http\Resources\BranchSelectResource;

class BranchController extends Controller
{
    public function index()
    {
        return Branch::with([
            'address.barangay.cityMun.province.region'
        ])->paginate(10);
    }

    public function show($id)
    {
        return Branch::with([
            'address.barangay.cityMun.province.region'
        ])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|unique:branches',
            'name' => 'required',
            'address_id' => 'required|exists:addresses,id',
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Branch created successfully.',
            'data' => $branch
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|unique:branches,code,' . $branch->id,
            'name' => 'required',
            'address_id' => 'required|exists:addresses,id',
            'is_active' => 'boolean'
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully.',
            'data' => $branch
        ]);
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully.'
        ]);
    }
    
    public function select()
    {
        $branches = Branch::query()
            ->with([
                'address',
                'address.barangay.cityMun.province.region'
            ])
            ->get();

        return BranchSelectResource::collection($branches);
    }
}

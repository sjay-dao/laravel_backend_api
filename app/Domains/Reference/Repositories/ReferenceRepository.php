<?php

namespace App\Domains\Reference\Repositories;

use App\Domains\Reference\Models\Reference;
use App\Domains\Reference\Models\ReferenceType;

class ReferenceRepository
{
    public function types()
    {
        return ReferenceType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function byType(string $type)
    {
        return Reference::query()
            ->whereHas('type', function ($q) use ($type) {
                $q->where('code', $type);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function find(string $type, string $code): ?Reference
    {
        return Reference::query()
            ->where('code', $code)
            ->whereHas('type', function ($q) use ($type) {
                $q->where('code', $type);
            })
            ->first();
    }

    public function create(array $data): Reference
    {
        return Reference::create($data);
    }

    public function update(Reference $reference, array $data): Reference
    {
        $reference->update($data);

        return $reference->refresh();
    }

    public function delete(Reference $reference): void
    {
        $reference->delete();
    }

    public function findById(int $id): ?Reference
    {
        return Reference::find($id);
    }

    public function findType(string $type): ?ReferenceType
    {
        return ReferenceType::where('code', $type)->first();
    }

    public function findTypeByCode(
        string $code
    ): ?ReferenceType
    {
        return ReferenceType::query()
            ->where('code', strtoupper($code))
            ->first();
    }

    public function all()
    {
        return Reference::query()
            ->with('type')
            ->orderBy('lookup_type_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
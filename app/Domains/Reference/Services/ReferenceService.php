<?php

namespace App\Domains\Reference\Services;

use App\Domains\Reference\Models\Reference;
use App\Domains\Reference\Models\ReferenceType;
use App\Domains\Reference\Repositories\ReferenceRepository;

class ReferenceService
{
    public function __construct(
        protected ReferenceRepository $repository,
        protected ReferenceCache $cache
    ) {
    }

    /**
     * Get all active reference types.
     */
    public function types()
    {
        return $this->repository->types();
    }

    /**
     * Get all active references of a type.
     */
    public function byType(string $type)
    {
        return $this->cache->rememberType(
            $type,
            fn() => $this->repository->byType($type)
        );
    }

    public function models(string $type)
    {
        return $this->repository->byType($type);
    }

    public function options(string $type)
    {
        return $this->cache->rememberType(
            $type,
            fn() => $this->repository
                ->byType($type)
                ->toArray()
        );
    }

    /**
     * Get a single reference.
     */
    public function find(string $type, string $code): ?Reference
    {
        return $this->cache->rememberItem(
            $type,
            $code,
            fn() => $this->repository->find($type, $code)
        );
    }

    /**
     * Find by database id.
     */
    public function findById(int $id): ?Reference
    {
        return $this->repository->findById($id);
    }

    /**
     * Get one lookup type.
     */
    public function findType(string $type)
    {
        return $this->repository->findType($type);
    }

    /**
     * Create a reference.
     */
    public function create(array $data)
    {
        $lookupType = ReferenceType::where('code', $data['type'])->firstOrFail();

        $data['lookup_type_id'] = $lookupType->id;

        unset($data['type']);

        $reference =  $this->repository->create($data);
        $type = $reference->type->code;

        $this->cache->forgetType($type);

        return $reference->refresh();
    }

    /**
     * Update a reference.
     */
    public function update(
        Reference $reference,
        array $data
    ): Reference {

        $oldType = $reference->type->code;
        $oldCode = $reference->code;

        $reference = $this->repository->update(
            $reference,
            $data
        );

        $newType = $reference->type->code;
        $newCode = $reference->code;

        /*
         * Forget old cache.
         */
        $this->cache->forgetType($oldType);
        $this->cache->forgetItem($oldType, $oldCode);

        /*
         * If type/code changed,
         * clear new cache too.
         */
        if (
            $oldType !== $newType ||
            $oldCode !== $newCode
        ) {
            $this->cache->forgetType($newType);
            $this->cache->forgetItem($newType, $newCode);
        }

        return $reference;
    }

    /**
     * Delete reference.
     */
    public function delete(Reference $reference): void
    {
        $type = $reference->type->code;
        $code = $reference->code;

        $this->repository->delete($reference);

        $this->cache->forgetType($type);
        $this->cache->forgetItem($type, $code);
    }

    /**
     * Bootstrap data for frontend.
     */
    public function bootstrap(array $types): array
    {
        $result = [];

        foreach ($types as $type) {
            $result[$type] = $this->byType($type);
        }

        return $result;
    }

    public function findTypeByCode(
        string $code
    )
    {
        return $this->repository
            ->findTypeByCode($code);
    }

    

    public function all()
    {
        return $this->repository->all();
    }
}
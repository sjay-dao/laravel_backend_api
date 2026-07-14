<?php

namespace App\Domains\Shared\Services;

use App\Domains\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

abstract class BaseCrudService
{
    protected BaseRepository $repository;

    public function paginate(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    public function all()
    {
        return $this->repository->all();
    }

    public function findById(int $id): Model
    {
        return $this->repository->findById($id);
    }

    public function delete(Model $model): bool
    {
        return $this->repository->delete($model);
    }
}
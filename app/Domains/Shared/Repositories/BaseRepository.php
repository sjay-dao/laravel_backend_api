<?php

namespace App\Domains\Shared\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    /**
     * Relationships to eager load by default.
     */
    protected array $with = [];

    public function paginate(int $perPage = 15)
    {
        return $this->query()->paginate($perPage);
    }

    public function all()
    {
        return $this->query()->get();
    }

    public function findById(int $id)
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return $model->delete();
    }

    protected function query()
    {
        return $this->model
            ->newQuery()
            ->with($this->with);
    }
}
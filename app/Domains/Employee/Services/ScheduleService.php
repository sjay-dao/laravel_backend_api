<?php

namespace App\Domains\Employee\Services;

use App\Domains\Employee\Repositories\ScheduleRepository;

class ScheduleService
{
    public function __construct(
        protected ScheduleRepository $repository
    ) {
    }

    public function index(array $filters = [])
    {
        return $this->repository->index($filters);
    }

    public function find(int $id)
    {
        return $this->repository->find($id);
    }

    public function store(array $data)
    {
        return $this->repository->store($data);
    }

    public function update(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }
}
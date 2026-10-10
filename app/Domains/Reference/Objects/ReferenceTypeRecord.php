<?php

namespace App\Domains\Reference\Objects;

use App\Domains\Reference\Models\ReferenceType;

class ReferenceTypeRecord
{
    public function __construct(
        protected ?ReferenceType $type
    ) {
    }

    public function id(): ?int
    {
        return $this->type?->id;
    }

    public function code(): ?string
    {
        return $this->type?->code;
    }

    public function name(): ?string
    {
        return $this->type?->name;
    }

    public function description(): ?string
    {
        return $this->type?->description;
    }

    public function active(): bool
    {
        return (bool) ($this->type?->is_active);
    }

    public function exists(): bool
    {
        return $this->type !== null;
    }

    public function toArray(): array
    {
        return $this->type?->toArray() ?? [];
    }
}
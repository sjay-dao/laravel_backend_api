<?php

namespace App\Domains\Reference\Services;

use App\Domains\Reference\Models\Reference;

class ReferenceRecord
{
    public function __construct(
        protected ?Reference $reference
    ) {
    }

    /**
     * Database id.
     */
    public function id(): ?int
    {
        return $this->reference?->id;
    }

    /**
     * Lookup code.
     */
    public function code(): ?string
    {
        return $this->reference?->code;
    }

    /**
     * Display name.
     */
    public function name(): ?string
    {
        return $this->reference?->name;
    }

    /**
     * Stored value.
     */
    public function value()
    {
        return $this->reference?->value;
    }

    /**
     * Description.
     */
    public function description(): ?string
    {
        return $this->reference?->description;
    }

    /**
     * Color.
     */
    public function color(): ?string
    {
        return $this->reference?->color;
    }

    /**
     * Icon.
     */
    public function icon(): ?string
    {
        return $this->reference?->icon;
    }

    /**
     * Metadata.
     */
    public function metadata(): array
    {
        return $this->reference?->metadata ?? [];
    }

    /**
     * Active?
     */
    public function active(): bool
    {
        return (bool) ($this->reference?->is_active);
    }

    /**
     * Exists?
     */
    public function exists(): bool
    {
        return $this->reference !== null;
    }

    /**
     * Array.
     */
    public function toArray(): array
    {
        return $this->reference?->toArray() ?? [];
    }

    public function type(): ?string
    {
        return $this->reference?->type?->code;
    }
}
<?php

namespace App\Domains\Reference\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class ReferenceCache
{
    protected function key(string $type): string
    {
        return "reference:type:{$type}";
    }

    protected function itemKey(string $type, string $code): string
    {
        return "reference:item:{$type}:{$code}";
    }

    public function rememberType(string $type, Closure $callback)
    {
        return Cache::remember(
            $this->key($type),
            now()->addDay(),
            function () use ($callback) {
                return $callback()->toArray();
            }
        );
    }

    public function rememberItem(
        string $type,
        string $code,
        Closure $callback
    ) {
        return Cache::remember(
            $this->itemKey($type, $code),
            now()->addDay(),
            $callback
        );
    }

    public function forgetType(string $type): void
    {
        Cache::forget($this->key($type));
    }

    public function forgetItem(
        string $type,
        string $code
    ): void {
        Cache::forget(
            $this->itemKey($type, $code)
        );
    }
}
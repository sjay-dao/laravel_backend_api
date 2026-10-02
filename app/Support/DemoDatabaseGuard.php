<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DemoDatabaseGuard
{
    public function assertSafe(): void
    {
        if (config('demo.enabled') !== true || ! app()->environment('local', 'testing', 'demo')) {
            throw new RuntimeException('Demo operations require DEMO_MODE=true and APP_ENV=local, testing or demo. Production is refused.');
        }
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        if ($connection->getDriverName() === 'sqlite' && $database === ':memory:' && app()->environment('testing')) {
            return;
        }
        if ($connection->getDriverName() !== 'mysql'
            || ! is_string($database)
            || ! preg_match('/^mica_demo(?:_[a-zA-Z0-9]+)*$/D', $database)
            || config('demo.database') !== $database
            || $connection->getPdo()->query('SELECT DATABASE()')->fetchColumn() !== $database
            || $connection->selectOne('SELECT DATABASE() AS name')->name !== $database
            || Schema::getConnection()->getPdo() !== $connection->getPdo()) {
            throw new RuntimeException('Use a dedicated mica_demo database and set DEMO_DATABASE to its exact name. No database was changed.');
        }
    }
}

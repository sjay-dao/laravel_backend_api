<?php

namespace Tests\Feature;

use App\Support\DemoDatabaseGuard;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class DemoDatabaseGuardTest extends TestCase
{
    public function test_disabled_demo_mode_refuses_before_any_database_connection(): void
    {
        config(['demo.enabled' => false]);
        DB::shouldReceive('connection')->never();
        $this->expectException(RuntimeException::class);
        app(DemoDatabaseGuard::class)->assertSafe();
    }

    public function test_production_refuses_even_with_explicit_demo_mode(): void
    {
        config(['demo.enabled' => true]);
        app()->instance('env', 'production');
        DB::shouldReceive('connection')->never();
        $this->expectException(RuntimeException::class);
        app(DemoDatabaseGuard::class)->assertSafe();
    }

    public static function unsafeConnections(): array
    {
        return [
            'development database' => ['mysql', 'laravel_backend_api', 'laravel_backend_api'],
            'dedicated name mismatch' => ['mysql', 'mica_demo_test', 'mica_demo_other'],
            'no explicit database' => ['mysql', 'mica_demo_test', null],
            'SQLite file' => ['sqlite', 'database.sqlite', 'database.sqlite'],
        ];
    }

    #[DataProvider('unsafeConnections')]
    public function test_unsafe_database_refused_without_issuing_sql(string $driver, string $database, ?string $declared): void
    {
        config(['demo.enabled' => true, 'demo.database' => $declared]);
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->andReturn($driver);
        $connection->shouldReceive('getDatabaseName')->andReturn($database);
        $connection->shouldNotReceive('getPdo');
        $connection->shouldNotReceive('selectOne');
        DB::shouldReceive('connection')->andReturn($connection);
        $this->expectException(RuntimeException::class);
        app(DemoDatabaseGuard::class)->assertSafe();
    }
}

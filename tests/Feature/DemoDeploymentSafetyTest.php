<?php

namespace Tests\Feature;

use App\Providers\DemoIsolationServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class DemoDeploymentSafetyTest extends TestCase
{
    private function isolate(): void
    {
        config(['demo.enabled' => true, 'app.debug' => true]);
        $provider = new DemoIsolationServiceProvider(app());
        $provider->register();
        $provider->boot();
    }

    public function test_demo_removes_external_connections_and_forces_safe_transports(): void
    {
        $this->isolate();
        $this->assertFalse(config('app.debug'));
        $this->assertSame(['sqlite'], array_keys(config('database.connections')));
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('sync', config('queue.default'));
        $this->assertFalse(config('dompdf.options.isRemoteEnabled'));
        $this->expectException(InvalidArgumentException::class);
        DB::connection('ers');
    }

    public function test_explicit_smtp_transport_cannot_bypass_demo_mail_isolation(): void
    {
        $this->isolate();
        $this->expectException(InvalidArgumentException::class);
        Mail::mailer('smtp');
    }

    public function test_outbound_http_is_refused_before_network_access(): void
    {
        $this->isolate();
        $this->expectException(RuntimeException::class);
        Http::get('https://never-contact.example.invalid');
    }

    public function test_health_endpoint_needs_no_database_and_exposes_no_configuration(): void
    {
        DB::shouldReceive('connection')->never();
        $this->get('/up')->assertOk()->assertDontSee('DB_PASSWORD')->assertDontSee(base_path());
    }

    public function test_demo_request_refuses_development_database_before_connection(): void
    {
        config(['demo.enabled' => true, 'database.default' => 'mysql', 'database.connections.mysql.database' => 'laravel_backend_api']);
        $this->getJson('/api/system/me')->assertStatus(503)->assertExactJson(['message' => 'Demo database configuration is unavailable.']);
    }

    public function test_cors_accepts_configured_vercel_origin_for_bearer_requests(): void
    {
        config(['cors.allowed_origins' => ['https://mica.example.vercel.app']]);
        $response = $this->withHeaders(['Origin' => 'https://mica.example.vercel.app', 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'Authorization,Content-Type'])->options('/api/login');
        $response->assertStatus(204)->assertHeader('Access-Control-Allow-Origin', 'https://mica.example.vercel.app');
        $response->assertHeaderMissing('Access-Control-Allow-Credentials');
    }
}

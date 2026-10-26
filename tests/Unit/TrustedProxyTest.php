<?php

namespace Tests\Unit;

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_direct_clients_cannot_spoof_their_address(): void
    {
        config(['trustedproxy.proxies' => '10.136.88.6']);
        $request = Request::create('https://example.test/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '198.51.100.25',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        ]);
        $response = (new TrustProxies)->handle($request, fn ($request) => new Response($request->ip()));
        $this->assertSame('198.51.100.25', $response->getContent());
    }

    public function test_the_old_server_preserves_the_original_client_address(): void
    {
        config(['trustedproxy.proxies' => '10.136.88.6']);
        $request = Request::create('https://example.test/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.136.88.6',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        ]);
        $response = (new TrustProxies)->handle($request, fn ($request) => new Response($request->ip()));
        $this->assertSame('203.0.113.10', $response->getContent());
    }
}

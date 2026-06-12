<?php

namespace Tests\Unit;

use App\Services\Syscom\TokenCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyscomHttpClientTest extends TestCase
{
    public function test_token_cache_returns_cached_token_when_valid(): void
    {
        Cache::put('syscom:test:token', 'cached-abc', 3600);

        $cache = new TokenCache('syscom:test:token', safetyMarginSeconds: 60);

        $this->assertSame('cached-abc', $cache->get());
    }

    public function test_token_cache_fetches_new_token_when_missing(): void
    {
        Cache::forget('syscom:test:token:missing');

        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => 'fresh-token-xyz',
            ], 200),
        ]);

        $cache = new TokenCache('syscom:test:token:missing', safetyMarginSeconds: 60);
        $token = $cache->get();

        $this->assertSame('fresh-token-xyz', $token);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/oauth/token'));
    }

    public function test_token_cache_throws_on_oauth_failure(): void
    {
        Cache::forget('syscom:test:token:failure');

        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $cache = new TokenCache('syscom:test:token:failure', safetyMarginSeconds: 60);

        $this->expectException(\RuntimeException::class);
        $cache->get();
    }
}

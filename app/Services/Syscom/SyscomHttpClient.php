<?php

namespace App\Services\Syscom;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SyscomHttpClient
{
    protected ?string $cachedToken = null;

    public function __construct(
        protected TokenCache $tokenCache,
        protected string $baseUrl,
    ) {}

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, query: $query);
    }

    public function post(string $path, array $json = []): array
    {
        return $this->request('POST', $path, json: $json);
    }

    public function put(string $path, array $json = []): array
    {
        return $this->request('PUT', $path, json: $json);
    }

    public function delete(string $path, array $query = []): array
    {
        return $this->request('DELETE', $path, query: $query);
    }

    protected function request(string $method, string $path, array $query = [], array $json = []): array
    {
        $url = $this->buildUrl($path);
        $attempts = 0;
        $maxAttempts = max(1, (int) config('syscom.http.retry_times', 3));
        $sleepMs = max(0, (int) config('syscom.http.retry_sleep_ms', 200));
        $lastError = null;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $token = $this->getToken();

            try {
                /** @var Response $response */
                $response = $this->buildRequest($token, $json)
                    ->{$this->httpMethod($method)}($url, $query);

                if ($response->status() === 401) {
                    $this->tokenCache->forget();
                    $this->cachedToken = null;
                    continue;
                }

                if ($response->status() === 429) {
                    $retryAfter = (int) ($response->header('Retry-After') ?? 1);
                    Log::warning('Syscom rate limited', [
                        'url' => $url,
                        'retry_after' => $retryAfter,
                        'attempt' => $attempts,
                    ]);
                    $this->sleepBackoff($retryAfter * 1000, $attempts);
                    continue;
                }

                if ($response->serverError() && $attempts < $maxAttempts) {
                    $this->sleepBackoff($sleepMs, $attempts);
                    continue;
                }

                if ($response->failed()) {
                    throw new RuntimeException(sprintf(
                        'Syscom %s %s failed [%d]: %s',
                        $method,
                        $path,
                        $response->status(),
                        $response->body()
                    ));
                }

                return $response->json() ?? [];
            } catch (RequestException $e) {
                $lastError = $e;
                if ($attempts < $maxAttempts) {
                    $this->sleepBackoff($sleepMs, $attempts);
                    continue;
                }
                break;
            } catch (Throwable $e) {
                $lastError = $e;
                if ($attempts < $maxAttempts) {
                    $this->sleepBackoff($sleepMs, $attempts);
                    continue;
                }
                break;
            }
        }

        Log::error('Syscom request exhausted retries', [
            'method' => $method,
            'path' => $path,
            'error' => $lastError?->getMessage(),
        ]);

        throw new RuntimeException(
            'Syscom request failed after '.$maxAttempts.' attempts: '.($lastError?->getMessage() ?? 'unknown'),
            previous: $lastError instanceof Throwable ? $lastError : null,
        );
    }

    protected function getToken(): string
    {
        if (is_string($this->cachedToken) && $this->cachedToken !== '') {
            return $this->cachedToken;
        }

        return $this->cachedToken = $this->tokenCache->get();
    }

    protected function buildRequest(string $token, array $json): PendingRequest
    {
        $request = Http::withToken($token)
            ->acceptJson()
            ->timeout((int) config('syscom.http.timeout', 15))
            ->connectTimeout((int) config('syscom.http.connect_timeout', 5));

        if ($json !== []) {
            $request = $request->withBody(
                json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'application/json'
            );
        }

        return $request;
    }

    protected function buildUrl(string $path): string
    {
        $base = rtrim($this->baseUrl, '/');
        $path = ltrim($path, '/');
        return $base.'/'.$path;
    }

    protected function httpMethod(string $method): string
    {
        return match ($method) {
            'GET' => 'get',
            'POST' => 'post',
            'PUT' => 'put',
            'DELETE' => 'delete',
            'PATCH' => 'patch',
            default => strtolower($method),
        };
    }

    protected function sleepBackoff(int $baseMs, int $attempt): void
    {
        $jitter = random_int(0, max(1, (int) ($baseMs / 2)));
        $delay = (int) (($baseMs * (2 ** ($attempt - 1))) + $jitter);
        usleep($delay * 1000);
    }
}

<?php

namespace App\Services\Syscom;

use App\Services\Syscom\DTOs\CategoryDto;
use Illuminate\Support\Facades\Cache;

class CategoriesService
{
    public function __construct(protected SyscomHttpClient $client) {}

    public function getCategories(): array
    {
        $ttl = (int) config('syscom.cache.categories_ttl', 86400);
        $cacheKey = 'syscom:categories:all';

        return Cache::remember($cacheKey, $ttl, function (): array {
            $data = $this->client->get('categorias');
            return array_map(
                static fn (array $c) => CategoryDto::fromArray($c)->toArray(),
                $data
            );
        });
    }

    public function getCategoryById(int $id): ?array
    {
        $ttl = (int) config('syscom.cache.categories_ttl', 86400);
        $cacheKey = "syscom:categories:{$id}";

        return Cache::remember($cacheKey, $ttl, function () use ($id): ?array {
            try {
                $data = $this->client->get("categorias/{$id}");
            } catch (\Throwable) {
                return null;
            }
            return $data ? CategoryDto::fromArray($data)->toArray() : null;
        });
    }
}

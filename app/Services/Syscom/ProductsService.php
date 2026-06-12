<?php

namespace App\Services\Syscom;

use App\Services\Syscom\DTOs\ProductsPageDto;
use Illuminate\Support\Facades\Cache;

class ProductsService
{
    public function __construct(protected SyscomHttpClient $client) {}

    public function getProducts(array $params): array
    {
        $query = $this->parseParams($params);
        $cacheKey = 'syscom:products:'.md5(json_encode($query, JSON_UNESCAPED_UNICODE));
        $ttl = (int) config('syscom.cache.products_ttl', 600);

        return Cache::remember($cacheKey, $ttl, function () use ($query): array {
            $data = $this->client->get('productos', $query);
            return ProductsPageDto::fromArray($data)->toArray();
        });
    }

    public function getProductById(int $id): ?array
    {
        $cacheKey = "syscom:product:{$id}";
        $ttl = (int) config('syscom.cache.product_ttl', 600);

        return Cache::remember($cacheKey, $ttl, function () use ($id): ?array {
            try {
                $data = $this->client->get("productos/{$id}");
            } catch (\Throwable) {
                return null;
            }
            return $data ?: null;
        });
    }

    public function getBrands(?int $categoriaId = null): array
    {
        $cacheKey = 'syscom:brands'.($categoriaId ? ":cat:{$categoriaId}" : ':all');
        $ttl = (int) config('syscom.cache.brands_ttl', 86400);

        return Cache::remember($cacheKey, $ttl, function () use ($categoriaId): array {
            $query = $categoriaId ? ['categoria' => $categoriaId] : [];
            try {
                $data = $this->client->get('marcas', $query);
                return is_array($data) ? $data : [];
            } catch (\Throwable) {
                return [];
            }
        });
    }

    public function getFeaturedCategoryId(): ?int
    {
        $cats = app(CategoriesService::class)->getCategories();
        if ($cats === []) {
            return null;
        }
        $preferredNames = ['Redes', 'Videovigilancia', 'Energía', 'Cableado Estructurado', 'Automatización'];
        foreach ($preferredNames as $name) {
            foreach ($cats as $c) {
                if (strcasecmp($c['nombre'] ?? '', $name) === 0) {
                    return (int) $c['id'];
                }
            }
        }
        return (int) $cats[0]['id'];
    }

    protected function parseParams(array $params): array
    {
        $query = [];

        if (isset($params['categoria']) && is_numeric($params['categoria'])) {
            $query['categoria'] = (int) $params['categoria'];
        }
        if (isset($params['marca']) && $params['marca'] !== '') {
            $query['marca'] = $params['marca'];
        }
        if (isset($params['stock']) && $params['stock'] !== '') {
            $query['stock'] = $params['stock'];
        }
        if (isset($params['busqueda']) && $params['busqueda'] !== '') {
            $query['busqueda'] = $params['busqueda'];
        }
        if (isset($params['pagina']) && is_numeric($params['pagina'])) {
            $query['pagina'] = max(1, (int) $params['pagina']);
        }
        if (isset($params['orden']) && $params['orden'] !== '') {
            $query['orden'] = $params['orden'];
        }
        if (isset($params['max']) && is_numeric($params['max'])) {
            $query['max'] = max(1, min(200, (int) $params['max']));
        }
        if (isset($params['precio_min']) && is_numeric($params['precio_min'])) {
            $query['precio_min'] = (float) $params['precio_min'];
        }
        if (isset($params['precio_max']) && is_numeric($params['precio_max'])) {
            $query['precio_max'] = (float) $params['precio_max'];
        }

        return $query;
    }
}

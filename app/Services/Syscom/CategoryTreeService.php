<?php

namespace App\Services\Syscom;

use App\Services\Syscom\DTOs\CategoryDto;
use Illuminate\Support\Facades\Cache;

class CategoryTreeService
{
    public function __construct(protected CategoriesService $categories) {}

    public function getTree(): array
    {
        $ttl = (int) config('syscom.cache.categories_ttl', 86400);
        $cacheKey = 'syscom:categories:tree:v2';

        return Cache::remember($cacheKey, $ttl, function (): array {
            $flat = $this->categories->getCategories();
            return $this->buildTree($flat);
        });
    }

    public function getRoots(): array
    {
        return array_values(array_filter(
            $this->getTree(),
            fn ($node) => ($node['parent_id'] ?? null) === null
        ));
    }

    public function getPath(int $categoryId): array
    {
        $flat = $this->categories->getCategories();
        $byId = [];
        $position = -1;
        foreach ($flat as $i => $c) {
            $id = (int) $c['id'];
            $byId[$id] = $c;
            if ($id === $categoryId) {
                $position = $i;
            }
        }

        if ($position === -1) {
            return [];
        }

        $current = $byId[$categoryId];
        $path = [$current];

        while (true) {
            $currentNivel = (int) ($current['nivel'] ?? 1);
            if ($currentNivel <= 1) {
                break;
            }
            $parentNivel = $currentNivel - 1;
            $parent = null;
            for ($i = $position - 1; $i >= 0; $i--) {
                if ((int) ($flat[$i]['nivel'] ?? 1) === $parentNivel) {
                    $parent = $flat[$i];
                    break;
                }
            }
            if ($parent === null) {
                break;
            }
            array_unshift($path, $parent);
            $current = $parent;
        }

        return $path;
    }

    public function getChildren(int $categoryId): array
    {
        $all = $this->getTree();
        return array_values(array_filter($all, fn ($c) => (int) ($c['parent_id'] ?? 0) === $categoryId));
    }

    public function getCategory(int $id): ?array
    {
        foreach ($this->getTree() as $node) {
            if ((int) $node['id'] === $id) {
                return $node;
            }
        }
        return null;
    }

    protected function buildTree(array $flat): array
    {
        $nodes = [];
        $lastByLevel = [];

        foreach ($flat as $c) {
            $id = (int) $c['id'];
            $nivel = (int) ($c['nivel'] ?? 1);
            $parentId = $this->inferParent($id, $nivel, $flat, $lastByLevel);

            $nodes[$id] = [
                'id' => $id,
                'nombre' => (string) ($c['nombre'] ?? ''),
                'nivel' => $nivel,
                'parent_id' => $parentId,
                'children' => [],
                'children_ids' => [],
                'product_count' => 0,
            ];

            $lastByLevel[$nivel] = $id;
        }

        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parentId = $node['parent_id'];
            if ($parentId !== null && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = &$node;
                $nodes[$parentId]['children_ids'][] = $id;
            } else {
                $tree[] = &$node;
            }
        }
        unset($node);

        return $tree;
    }

    protected function inferParent(int $id, int $nivel, array $flat, array $lastByLevel): ?int
    {
        if ($nivel <= 1) {
            return null;
        }

        $parentNivel = $nivel - 1;
        if (isset($lastByLevel[$parentNivel])) {
            return (int) $lastByLevel[$parentNivel];
        }

        $current = null;
        foreach ($flat as $c) {
            if ((int) $c['id'] === $id) {
                $current = $c;
                break;
            }
        }
        if ($current === null) {
            return null;
        }

        $name = mb_strtolower(trim((string) ($current['nombre'] ?? '')));
        $bestMatch = null;
        $bestLen = -1;
        foreach ($flat as $candidate) {
            if ((int) $candidate['id'] === $id) {
                continue;
            }
            if ((int) ($candidate['nivel'] ?? 1) !== $parentNivel) {
                continue;
            }
            $candidateName = mb_strtolower(trim((string) ($candidate['nombre'] ?? '')));
            if ($candidateName !== '' && str_starts_with($name, $candidateName) && mb_strlen($candidateName) > $bestLen) {
                $bestMatch = (int) $candidate['id'];
                $bestLen = mb_strlen($candidateName);
            }
        }

        return $bestMatch;
    }

    protected function findParentId(int $childId, array $flat, array $byId): ?int
    {
        $child = $byId[$childId] ?? null;
        if ($child === null) {
            return null;
        }
        $name = mb_strtolower(trim((string) ($child['nombre'] ?? '')));
        $bestMatch = null;
        $bestLen = -1;
        foreach ($flat as $candidate) {
            if ((int) $candidate['id'] === $childId) {
                continue;
            }
            $nivel = (int) ($candidate['nivel'] ?? 1);
            if ($nivel !== ((int) ($child['nivel'] ?? 1)) - 1) {
                continue;
            }
            $candidateName = mb_strtolower(trim((string) ($candidate['nombre'] ?? '')));
            if ($candidateName !== '' && str_starts_with($name, $candidateName) && mb_strlen($candidateName) > $bestLen) {
                $bestMatch = (int) $candidate['id'];
                $bestLen = mb_strlen($candidateName);
            }
        }
        return $bestMatch;
    }
}

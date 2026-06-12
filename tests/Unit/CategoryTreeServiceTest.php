<?php

namespace Tests\Unit;

use App\Services\Syscom\CategoriesService;
use App\Services\Syscom\CategoryTreeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CategoryTreeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_builds_tree_from_flat_categories(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/categorias' => Http::response([
                ['id' => '1', 'nombre' => 'Redes', 'nivel' => 1],
                ['id' => '2', 'nombre' => 'Redes Inalámbricas', 'nivel' => 2],
                ['id' => '3', 'nombre' => 'Redes Cableadas', 'nivel' => 2],
                ['id' => '4', 'nombre' => 'Videovigilancia', 'nivel' => 1],
            ], 200),
        ]);

        $tree = app(CategoryTreeService::class)->getTree();
        $this->assertCount(2, $tree, 'Dos categorías raíz: Redes, Videovigilancia');

        $redes = collect($tree)->firstWhere('nombre', 'Redes');
        $this->assertNotNull($redes);
        $this->assertNull($redes['parent_id']);
        $this->assertCount(2, $redes['children']);

        $inalambricas = collect($redes['children'])->firstWhere('nombre', 'Redes Inalámbricas');
        $this->assertNotNull($inalambricas);
        $this->assertSame(1, $inalambricas['parent_id']);
    }

    public function test_get_roots_returns_only_top_level(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/categorias' => Http::response([
                ['id' => '1', 'nombre' => 'Redes', 'nivel' => 1],
                ['id' => '2', 'nombre' => 'Redes Inalámbricas', 'nivel' => 2],
                ['id' => '3', 'nombre' => 'Videovigilancia', 'nivel' => 1],
            ], 200),
        ]);

        $roots = app(CategoryTreeService::class)->getRoots();
        $this->assertCount(2, $roots);
        $this->assertEquals(['Redes', 'Videovigilancia'], collect($roots)->pluck('nombre')->all());
    }

    public function test_get_path_returns_hierarchy_chain(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/categorias' => Http::response([
                ['id' => '1', 'nombre' => 'Redes', 'nivel' => 1],
                ['id' => '2', 'nombre' => 'Redes Inalámbricas', 'nivel' => 2],
                ['id' => '3', 'nombre' => 'Access Points', 'nivel' => 3],
            ], 200),
        ]);

        $path = app(CategoryTreeService::class)->getPath(3);
        $this->assertCount(3, $path);
        $this->assertSame('Redes', $path[0]['nombre']);
        $this->assertSame('Redes Inalámbricas', $path[1]['nombre']);
        $this->assertSame('Access Points', $path[2]['nombre']);
    }

    public function test_handles_empty_categories(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/categorias' => Http::response([], 200),
        ]);

        $tree = app(CategoryTreeService::class)->getTree();
        $this->assertSame([], $tree);
    }

    public function test_caches_tree(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/categorias' => Http::response([
                ['id' => '1', 'nombre' => 'Redes', 'nivel' => 1],
            ], 200),
        ]);

        $service = app(CategoryTreeService::class);
        $service->getTree();
        $service->getTree();

        Http::assertSentCount(2);
    }
}

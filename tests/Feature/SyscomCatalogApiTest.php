<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyscomCatalogApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_products_index_without_filter_falls_back_to_featured_category(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/categorias' => Http::response([
                ['id' => '26', 'nombre' => 'Redes', 'nivel' => 1],
                ['id' => '22', 'nombre' => 'Videovigilancia', 'nivel' => 1],
            ], 200),
            'developers.syscom.mx/api/v1/productos*' => Http::response([
                'cantidad' => 1,
                'pagina' => 1,
                'paginas' => 1,
                'todo' => true,
                'productos' => [
                    ['producto_id' => 100, 'titulo' => 'Router', 'modelo' => 'R1', 'marca' => 'M', 'img_portada' => null, 'total_existencia' => 5, 'nombre' => 'Router', 'etiqueta' => null, 'categorias' => [], 'garantia' => '12m', 'precios' => ['precio_1' => 100, 'precio_descuento' => 0, 'precio_especial' => 0, 'precio_lista' => 100]],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/syscom/products');

        $response->assertOk();
        $response->assertJsonPath('cantidad', 1);
        $response->assertJsonPath('productos.0.producto_id', 100);
    }

    public function test_products_index_with_filters_passes_them_through(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos*' => Http::response([
                'cantidad' => 0, 'pagina' => 1, 'paginas' => 1, 'todo' => true, 'productos' => [],
            ], 200),
        ]);

        $this->getJson('/api/syscom/products?categoria=26&marca=Cisco&busqueda=router&pagina=2&orden=precio_asc&precio_min=100&precio_max=500&max=24')
            ->assertOk()
            ->assertJsonPath('cantidad', 0);
    }

    public function test_brands_endpoint_returns_brand_list(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/marcas*' => Http::response([
                ['nombre' => 'Cisco'], ['nombre' => 'Mikrotik'], ['nombre' => 'Ubiquiti'],
            ], 200),
        ]);

        $response = $this->getJson('/api/syscom/products/brands');
        $response->assertOk();
        $response->assertJsonPath('data.0.nombre', 'Cisco');
    }

    public function test_category_tree_returns_hierarchy(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/categorias' => Http::response([
                ['id' => '1', 'nombre' => 'Redes', 'nivel' => 1],
                ['id' => '2', 'nombre' => 'Redes Inalámbricas', 'nivel' => 2],
            ], 200),
        ]);

        $response = $this->getJson('/api/syscom/categories/tree');
        $response->assertOk();
        $response->assertJsonPath('data.0.nombre', 'Redes');
        $response->assertJsonPath('data.0.children.0.nombre', 'Redes Inalámbricas');
    }

    public function test_category_path_returns_404_for_unknown(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/categorias' => Http::response([
                ['id' => '1', 'nombre' => 'Redes', 'nivel' => 1],
            ], 200),
        ]);

        $this->getJson('/api/syscom/categories/999/path')->assertStatus(404);
    }
}

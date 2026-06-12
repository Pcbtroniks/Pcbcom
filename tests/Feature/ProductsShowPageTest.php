<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductsShowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_renders_with_valid_product(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/12345' => Http::response($this->fakeProduct(12345, 99.99, 'Router Wi-Fi 6'), 200),
        ]);

        $response = $this->get('/productos/12345');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Products/Show')
            ->where('productoId', 12345)
        );
    }

    public function test_show_page_returns_404_for_unknown_product_id(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/99999' => Http::response(['error' => 'Producto no encontrado'], 404),
        ]);

        $response = $this->get('/productos/99999');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Products/Show')->where('productoId', 99999));
    }

    public function test_show_page_with_string_id_casts_to_int(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/7' => Http::response($this->fakeProduct(7, 50), 200),
        ]);

        $response = $this->get('/productos/7');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('productoId', 7));
    }

    protected function fakeProduct(int $id, float $price, string $titulo = 'Test Product'): array
    {
        return [
            'producto_id' => $id,
            'titulo' => $titulo,
            'modelo' => "MDL-$id",
            'marca' => 'TestBrand',
            'img_portada' => "https://picsum.photos/seed/$id/400",
            'total_existencia' => 5,
            'nombre' => $titulo,
            'etiqueta' => null,
            'categorias' => [['id' => 26, 'nombre' => 'Redes', 'nivel' => 1]],
            'garantia' => '12 meses',
            'precios' => [
                'precio_1' => $price,
                'precio_descuento' => 0,
                'precio_especial' => 0,
                'precio_lista' => $price,
            ],
        ];
    }
}

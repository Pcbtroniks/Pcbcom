<?php

namespace Tests\Unit;

use App\Services\Syscom\DTOs\CategoryDto;
use App\Services\Syscom\DTOs\PriceDto;
use App\Services\Syscom\DTOs\ProductDto;
use App\Services\Syscom\DTOs\ProductsPageDto;
use Tests\TestCase;

class SyscomDtoTest extends TestCase
{
    public function test_price_dto_effective_picks_especial_when_present(): void
    {
        $price = new PriceDto(
            precio_1: 100.0,
            precio_descuento: 90.0,
            precio_especial: 80.0,
            precio_lista: 110.0,
        );

        $this->assertSame(80.0, $price->effective());
    }

    public function test_price_dto_effective_falls_back_to_descuento_then_precio_1_then_lista(): void
    {
        $a = new PriceDto(100.0, 90.0, 0.0, 110.0);
        $this->assertSame(90.0, $a->effective());

        $b = new PriceDto(100.0, 0.0, 0.0, 110.0);
        $this->assertSame(100.0, $b->effective());

        $c = new PriceDto(0.0, 0.0, 0.0, 110.0);
        $this->assertSame(110.0, $c->effective());
    }

    public function test_category_dto_parses_payload(): void
    {
        $dto = CategoryDto::fromArray(['id' => '22', 'nombre' => 'Videovigilancia', 'nivel' => '1']);

        $this->assertSame(22, $dto->id);
        $this->assertSame('Videovigilancia', $dto->nombre);
        $this->assertSame(1, $dto->nivel);
    }

    public function test_product_dto_parses_nested_structures(): void
    {
        $payload = [
            'producto_id' => 8421,
            'titulo' => 'Router Wi-Fi 6',
            'modelo' => 'RB-9000',
            'marca' => 'Mikrotik',
            'img_portada' => 'https://cdn.syscom.mx/rb9000.jpg',
            'total_existencia' => 12,
            'nombre' => 'Router Wi-Fi 6 Dual Band',
            'etiqueta' => 'NUEVO',
            'categorias' => [
                ['id' => '26', 'nombre' => 'Redes', 'nivel' => 1],
            ],
            'garantia' => '12 meses',
            'precios' => [
                'precio_1' => 199.5,
                'precio_descuento' => 0,
                'precio_especial' => 0,
                'precio_lista' => 220.0,
            ],
        ];

        $dto = ProductDto::fromArray($payload);

        $this->assertSame(8421, $dto->producto_id);
        $this->assertCount(1, $dto->categorias);
        $this->assertSame('Redes', $dto->categorias[0]->nombre);
        $this->assertSame(199.5, $dto->precios->precio_1);
    }

    public function test_products_page_dto_wraps_collection(): void
    {
        $payload = [
            'cantidad' => 1,
            'pagina' => 1,
            'paginas' => 1,
            'productos' => [[
                'producto_id' => 1,
                'titulo' => 'X',
                'modelo' => 'M',
                'marca' => 'B',
                'img_portada' => null,
                'total_existencia' => 0,
                'nombre' => 'X',
                'etiqueta' => null,
                'categorias' => [],
                'garantia' => null,
                'precios' => ['precio_1' => 1, 'precio_descuento' => 0, 'precio_especial' => 0, 'precio_lista' => 1],
            ]],
            'todo' => true,
        ];

        $page = ProductsPageDto::fromArray($payload);

        $this->assertCount(1, $page->productos);
        $this->assertTrue($page->todo);
    }
}

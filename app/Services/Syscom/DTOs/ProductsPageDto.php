<?php

namespace App\Services\Syscom\DTOs;

class ProductsPageDto
{
    public function __construct(
        public readonly int $cantidad,
        public readonly int $pagina,
        public readonly int $paginas,
        public readonly array $productos,
        public readonly bool $todo,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cantidad: (int) ($data['cantidad'] ?? 0),
            pagina: (int) ($data['pagina'] ?? 1),
            paginas: (int) ($data['paginas'] ?? 1),
            productos: array_map(
                static fn (array $p) => ProductDto::fromArray($p),
                $data['productos'] ?? []
            ),
            todo: (bool) ($data['todo'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'cantidad' => $this->cantidad,
            'pagina' => $this->pagina,
            'paginas' => $this->paginas,
            'productos' => array_map(static fn (ProductDto $p) => $p->toArray(), $this->productos),
            'todo' => $this->todo,
        ];
    }
}

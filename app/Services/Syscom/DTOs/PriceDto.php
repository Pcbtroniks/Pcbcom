<?php

namespace App\Services\Syscom\DTOs;

class PriceDto
{
    public function __construct(
        public readonly float $precio_1,
        public readonly float $precio_descuento,
        public readonly float $precio_especial,
        public readonly float $precio_lista,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            precio_1: (float) ($data['precio_1'] ?? 0),
            precio_descuento: (float) ($data['precio_descuento'] ?? 0),
            precio_especial: (float) ($data['precio_especial'] ?? 0),
            precio_lista: (float) ($data['precio_lista'] ?? 0),
        );
    }

    public function effective(): float
    {
        if ($this->precio_especial > 0) {
            return $this->precio_especial;
        }
        if ($this->precio_descuento > 0) {
            return $this->precio_descuento;
        }
        return $this->precio_1 > 0 ? $this->precio_1 : $this->precio_lista;
    }

    public function toArray(): array
    {
        return [
            'precio_1' => $this->precio_1,
            'precio_descuento' => $this->precio_descuento,
            'precio_especial' => $this->precio_especial,
            'precio_lista' => $this->precio_lista,
        ];
    }
}

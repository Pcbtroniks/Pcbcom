<?php

namespace App\Services\Syscom\DTOs;

class CategoryDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $nivel,
        public readonly string $nombre,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nivel: (int) ($data['nivel'] ?? 1),
            nombre: (string) $data['nombre'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nivel' => $this->nivel,
            'nombre' => $this->nombre,
        ];
    }
}

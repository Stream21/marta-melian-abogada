<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\TipoCasoId;

final readonly class TipoCaso
{
    public function __construct(
        private TipoCasoId $id,
        private string $nombre,
        private string $descripcion,
    ) {
    }

    public function id(): TipoCasoId
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function withNombreDescripcion(string $nombre, string $descripcion): self
    {
        return new self(
            $this->id,
            $nombre,
            $descripcion,
        );
    }
}

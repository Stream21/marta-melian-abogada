<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class CrearServicioInput
{
    public function __construct(
        public string $nombre,
        public string $tipo,
    ) {
    }
}

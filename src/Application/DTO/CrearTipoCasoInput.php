<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class CrearTipoCasoInput
{
    public function __construct(
        public string $nombre,
        public string $descripcion,
    ) {
    }
}

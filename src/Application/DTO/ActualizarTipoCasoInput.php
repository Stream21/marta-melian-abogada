<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ActualizarTipoCasoInput
{
    public function __construct(
        public string $id,
        public string $nombre,
        public string $descripcion,
    ) {
    }
}

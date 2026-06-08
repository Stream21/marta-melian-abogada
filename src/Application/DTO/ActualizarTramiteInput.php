<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ActualizarTramiteInput
{
    public function __construct(
        public string $id,
        public string $servicioId,
        public string $nombre,
        public float $honorarios,
        public string $plataforma,
        public bool $requiereProcurador,
    ) {
    }
}

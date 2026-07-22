<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Entrada del catálogo de nacionalidades (ISO 3166-1 alpha-3 → etiqueta en español).
 */
final readonly class Nacionalidad
{
    public function __construct(
        public string $codigo,
        public string $nombre,
    ) {
    }
}

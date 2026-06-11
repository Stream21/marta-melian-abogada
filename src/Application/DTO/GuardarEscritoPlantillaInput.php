<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class GuardarEscritoPlantillaInput
{
    /**
     * @param list<array<string, mixed>> $bloques
     */
    public function __construct(
        public string $tramiteId,
        public string $tipo,
        public array $bloques,
    ) {
    }
}

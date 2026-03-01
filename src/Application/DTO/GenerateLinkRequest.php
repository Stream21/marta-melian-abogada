<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class GenerateLinkRequest
{
    public function __construct(
        public string $expedienteId,
        public string $amount,
        public string $phone,
    ) {
    }
}

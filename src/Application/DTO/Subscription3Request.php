<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class Subscription3Request
{
    public function __construct(
        public string $expedienteId,
        public string $amount,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class PaymentResponse
{
    public function __construct(
        public string $id,
        public string $expedienteId,
        public string $status,
        public string $type,
        public string $amount,
        public ?string $pdfUrl = null,
        public string $createdAt = '',
    ) {
    }
}

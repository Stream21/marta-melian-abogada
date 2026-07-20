<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ManualPaymentRequest
{
    public function __construct(
        public string $expedienteId,
        public string $amount,
        public string $clientName = '',
        public string $caseReference = '',
        public ?int $cuotaNumero = null,
    ) {
    }
}

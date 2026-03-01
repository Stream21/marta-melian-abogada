<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ExpedienteResponse
{
    public function __construct(
        public string $id,
        public string $numero,
        public string $titulo,
        public string $estado,
        public string $fechaApertura,
        public string $clientName = '',
        public string $caseReference = '',
        public string $folderPath = '',
        public string $paymentStatus = 'pending',
    ) {
    }
}

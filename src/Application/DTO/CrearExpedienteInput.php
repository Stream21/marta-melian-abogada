<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class CrearExpedienteInput
{
    public function __construct(
        public string $numero,
        public string $titulo,
        public string $estado = 'abierto',
        public string $clientName = '',
        public string $caseReference = '',
        public string $folderPath = '',
    ) {
    }
}

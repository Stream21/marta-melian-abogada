<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class AltaExpedienteResult
{
    /**
     * @param string[] $canalesNotificados
     */
    public function __construct(
        public ExpedienteResponse $expediente,
        public string $accessUrl,
        public array $canalesNotificados,
    ) {
    }
}

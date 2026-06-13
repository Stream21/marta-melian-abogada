<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class AltaExpedienteInput
{
    public function __construct(
        public ?string $clienteId,
        public ?string $telefono,
        public ?string $email,
        public string $tramiteId,
        public float $honorariosAcordados,
        public string $metodoPago,
        public string $planPago,
        public int $numCuotas,
        public bool $notificar = true,
        /** @var string[] */
        public array $canalesNotificacion = [],
        public ?string $fechaVencimientoFase = null,
    ) {
    }
}

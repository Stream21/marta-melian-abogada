<?php

declare(strict_types=1);

namespace App\Application\Message;

/**
 * Job asíncrono: notificar al cliente por los canales del expediente.
 *
 * @param list<string>|null $canalesOverride Si se indica, sustituye a expediente->canalesNotificacion()
 */
final readonly class NotificarClienteMessage
{
    /**
     * @param list<string>|null $canalesOverride
     */
    public function __construct(
        public string $expedienteId,
        public string $tipo,
        public string $asunto,
        public string $mensaje,
        public ?string $hitoTipo = null,
        public ?string $hitoDescripcionPlantilla = null,
        public ?array $canalesOverride = null,
    ) {
    }
}

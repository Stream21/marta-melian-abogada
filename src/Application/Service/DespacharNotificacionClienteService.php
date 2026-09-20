<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Message\NotificarClienteMessage;
use App\Domain\Entity\Expediente;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Encola notificaciones al cliente (Symfony Messenger async).
 */
final class DespacharNotificacionClienteService
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param list<string>|null $canalesOverride
     *
     * @return list<string> Canales que se intentarán (para respuesta API inmediata)
     */
    public function despachar(
        Expediente $expediente,
        string $tipo,
        string $asunto,
        string $mensaje,
        ?string $hitoTipo = null,
        ?string $hitoDescripcionPlantilla = null,
        ?array $canalesOverride = null,
    ): array {
        $this->messageBus->dispatch(new NotificarClienteMessage(
            $expediente->id()->value(),
            $tipo,
            $asunto,
            $mensaje,
            $hitoTipo,
            $hitoDescripcionPlantilla,
            $canalesOverride,
        ));

        if (null !== $canalesOverride) {
            return Expediente::normalizarCanales($canalesOverride);
        }

        return $expediente->canalesNotificacion();
    }
}

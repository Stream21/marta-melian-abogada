<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ContratacionRepositoryInterface;

final class MarcarNotificacionLeidaUseCase
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
    ) {
    }

    public function __invoke(string $hitoId): void
    {
        $hitoId = trim($hitoId);
        if ('' === $hitoId) {
            throw new \InvalidArgumentException('Identificador de notificación no válido.');
        }

        $hito = $this->contratacionRepository->findHitoById($hitoId);
        if (null === $hito) {
            throw new \InvalidArgumentException('Notificación no encontrada.');
        }

        $this->contratacionRepository->marcarHitoLeido($hitoId);
    }
}

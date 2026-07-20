<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\NotificacionDestinoResolver;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;

final class MarcarTodasNotificacionesLeidasUseCase
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private NotificacionDestinoResolver $destinoResolver,
    ) {
    }

    public function __invoke(): int
    {
        $leidas = array_flip($this->contratacionRepository->findHitosLeidosIds());
        $hitos = $this->contratacionRepository->findRecentHitos(500);
        $marcadas = 0;

        foreach ($hitos as $hito) {
            if (isset($leidas[$hito->id()])) {
                continue;
            }

            if (!$this->destinoResolver->esNotificable($hito->tipo(), $hito->actor())) {
                continue;
            }

            if (null === $this->expedienteRepository->findById($hito->expedienteId())) {
                continue;
            }

            $this->contratacionRepository->marcarHitoLeido($hito->id());
            $leidas[$hito->id()] = true;
            ++$marcadas;
        }

        return $marcadas;
    }
}

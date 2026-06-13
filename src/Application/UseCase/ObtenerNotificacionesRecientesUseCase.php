<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;

final class ObtenerNotificacionesRecientesUseCase
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(int $limit = 20): array
    {
        $hitos = $this->contratacionRepository->findRecentHitos($limit);
        $items = [];

        foreach ($hitos as $hito) {
            $expediente = $this->expedienteRepository->findById($hito->expedienteId());
            if (null === $expediente) {
                continue;
            }

            $items[] = [
                'id' => $hito->id(),
                'tipo' => $hito->tipo(),
                'descripcion' => $hito->descripcion(),
                'actor' => $hito->actor()->value,
                'paso' => $hito->paso()?->value,
                'expedienteId' => $expediente->id()->value(),
                'expedienteNumero' => $expediente->numero(),
                'clienteNombre' => $expediente->clientName(),
                'createdAt' => $hito->createdAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return $items;
    }
}

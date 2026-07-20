<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\NotificacionDestinoResolver;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;

final class ObtenerNotificacionesRecientesUseCase
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private NotificacionDestinoResolver $destinoResolver,
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function __invoke(int $limit = 20): array
    {
        $leidas = array_flip($this->contratacionRepository->findHitosLeidosIds());
        $hitos = $this->contratacionRepository->findRecentHitos(500);
        $items = [];
        $total = 0;

        foreach ($hitos as $hito) {
            if (isset($leidas[$hito->id()])) {
                continue;
            }

            if (!$this->destinoResolver->esNotificable($hito->tipo(), $hito->actor())) {
                continue;
            }

            $expediente = $this->expedienteRepository->findById($hito->expedienteId());
            if (null === $expediente) {
                continue;
            }

            ++$total;

            if (count($items) >= $limit) {
                continue;
            }

            $paso = $hito->paso()?->value;
            $referenciaId = $hito->referenciaId();
            $destino = $this->destinoResolver->resolve($hito->tipo(), $paso, $referenciaId, $hito->id());

            $items[] = [
                'id' => $hito->id(),
                'tipo' => $hito->tipo(),
                'descripcion' => $hito->descripcion(),
                'actor' => $hito->actor()->value,
                'paso' => $paso,
                'referenciaId' => $referenciaId,
                'expedienteId' => $expediente->id()->value(),
                'expedienteNumero' => $expediente->numero(),
                'clienteNombre' => $expediente->clientName(),
                'createdAt' => $hito->createdAt()->format(\DateTimeInterface::ATOM),
                'destinoTab' => $destino['tab'],
                'destinoHitoId' => $destino['hitoId'],
                'destinoPaso' => $destino['paso'],
                'destinoReferenciaId' => $destino['referenciaId'],
                'abrirRevision' => $destino['abrirRevision'],
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}

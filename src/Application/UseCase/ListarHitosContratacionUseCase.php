<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ListarHitosContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, offset: int, limit: int, hasMore: bool}
     */
    public function __invoke(string $expedienteId, int $offset = 0, int $limit = 20): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $offset = max(0, $offset);
        $limit = min(50, max(1, $limit));
        $total = $this->contratacionRepository->countHitosByExpediente($id);
        $hitos = $this->contratacionRepository->findHitosByExpedientePaginated($id, $offset, $limit);

        return [
            'items' => array_map(fn ($h) => [
                'id' => $h->id(),
                'paso' => $h->paso()?->value,
                'tipo' => $h->tipo(),
                'descripcion' => $h->descripcion(),
                'actor' => $h->actor()->value,
                'createdAt' => $h->createdAt()->format(\DateTimeInterface::ATOM),
            ], $hitos),
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'hasMore' => $offset + $limit < $total,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ExpedienteNota;
use App\Domain\Repository\ExpedienteNotaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ListarNotasExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteNotaRepositoryInterface $notaRepository,
    ) {
    }

    /**
     * @return list<array{
     *     id: string,
     *     contenido: string,
     *     archivada: bool,
     *     archivadaAt: string|null,
     *     createdAt: string
     * }>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        return array_map(
            static fn (ExpedienteNota $nota): array => [
                'id' => $nota->id()->value(),
                'contenido' => $nota->contenido(),
                'archivada' => $nota->archivada(),
                'archivadaAt' => $nota->archivadaAt()?->format(\DateTimeInterface::ATOM),
                'createdAt' => $nota->createdAt()->format(\DateTimeInterface::ATOM),
            ],
            $this->notaRepository->findByExpediente($id),
        );
    }
}

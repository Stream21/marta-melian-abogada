<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ExpedienteNota;
use App\Domain\Repository\ExpedienteNotaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteNotaId;

final class CrearNotaExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteNotaRepositoryInterface $notaRepository,
    ) {
    }

    /**
     * @return array{
     *     id: string,
     *     contenido: string,
     *     archivada: bool,
     *     archivadaAt: string|null,
     *     createdAt: string
     * }
     */
    public function __invoke(string $expedienteId, string $contenido): array
    {
        $contenido = trim($contenido);
        if ('' === $contenido) {
            throw new \InvalidArgumentException('El contenido de la nota es obligatorio.');
        }

        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $nota = new ExpedienteNota(
            ExpedienteNotaId::generate(),
            $id,
            $contenido,
        );
        $this->notaRepository->save($nota);

        return [
            'id' => $nota->id()->value(),
            'contenido' => $nota->contenido(),
            'archivada' => $nota->archivada(),
            'archivadaAt' => null,
            'createdAt' => $nota->createdAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}

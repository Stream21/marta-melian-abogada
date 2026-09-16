<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ExpedienteNotaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteNotaId;

final class ArchivarNotaExpedienteUseCase
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
    public function __invoke(string $expedienteId, string $notaId, bool $archivar = true): array
    {
        $expId = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($expId);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $nota = $this->notaRepository->findById(new ExpedienteNotaId($notaId));
        if (null === $nota || !$nota->expedienteId()->equals($expId)) {
            throw new \InvalidArgumentException('Nota no encontrada.');
        }

        $actualizada = $archivar ? $nota->archivar() : $nota->desarchivar();
        $this->notaRepository->save($actualizada);

        return [
            'id' => $actualizada->id()->value(),
            'contenido' => $actualizada->contenido(),
            'archivada' => $actualizada->archivada(),
            'archivadaAt' => $actualizada->archivadaAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $actualizada->createdAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}

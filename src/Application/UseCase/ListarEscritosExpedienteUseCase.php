<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ListarEscritosExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteEscritoRepositoryInterface $escritoRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Contratacion === $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('Los escritos no están disponibles en fase de contratación.');
        }

        $escritos = $this->escritoRepository->findByExpediente($id);

        return array_map(static fn ($escrito) => [
            'id' => $escrito->id(),
            'titulo' => $escrito->titulo(),
            'createdAt' => $escrito->createdAt()->format(\DateTimeInterface::ATOM),
            'pdfUrl' => '/api/expedientes/' . $expedienteId . '/escritos/' . $escrito->id() . '/pdf',
        ], $escritos);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ObtenerEscritoExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteEscritoRepositoryInterface $escritoRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $expedienteId, string $escritoId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $escrito = $this->escritoRepository->findById($escritoId);
        if (null === $escrito || !$escrito->expedienteId()->equals($id)) {
            throw new \InvalidArgumentException('Escrito no encontrado.');
        }

        return [
            'id' => $escrito->id(),
            'titulo' => $escrito->titulo(),
            'contenidoHtml' => $escrito->contenidoHtml(),
            'createdAt' => $escrito->createdAt()->format(\DateTimeInterface::ATOM),
            'pdfUrl' => '/api/expedientes/' . $expedienteId . '/escritos/' . $escrito->id() . '/pdf',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class VincularExpedienteClienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    public function __invoke(string $expedienteId, ?string $clienteId, ?string $tramiteId): void
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $updated = $expediente->withClienteId($clienteId)->withTramiteId($tramiteId);
        $this->expedienteRepository->save($updated);
    }
}

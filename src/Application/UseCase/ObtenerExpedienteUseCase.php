<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ExpedienteResponseMapper;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ObtenerExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private string $frontendBaseUrl,
    ) {
    }

    public function __invoke(string $expedienteId): \App\Application\DTO\ExpedienteResponse
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        return ExpedienteResponseMapper::fromDomain($expediente, $this->frontendBaseUrl);
    }
}

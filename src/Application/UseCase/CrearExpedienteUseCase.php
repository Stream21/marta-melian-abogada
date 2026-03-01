<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\CrearExpedienteInput;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class CrearExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    public function __invoke(CrearExpedienteInput $input): Expediente
    {
        $expediente = new Expediente(
            ExpedienteId::generate(),
            $input->numero,
            $input->titulo,
            EstadoExpediente::from($input->estado),
            new \DateTimeImmutable('now'),
            $input->clientName,
            $input->caseReference,
            $input->folderPath,
            'pending',
        );

        $this->expedienteRepository->save($expediente);

        return $expediente;
    }
}

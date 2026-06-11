<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ContratacionPaso;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class InicializarContratacionUseCase
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
    ) {
    }

    public function __invoke(ExpedienteId $expedienteId): void
    {
        $existentes = $this->contratacionRepository->findPasosByExpediente($expedienteId);
        if ([] !== $existentes) {
            return;
        }

        foreach (PasoContratacionCliente::ordenados() as $paso) {
            $this->contratacionRepository->savePaso(new ContratacionPaso(
                $this->generateId(),
                $expedienteId,
                $paso,
                EstadoPasoContratacion::Pendiente,
            ));
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            $this->generateId(),
            $expedienteId,
            'contratacion_iniciada',
            'Expediente abierto en fase de contratación. Enlace enviado al cliente.',
            ActorHitoExpediente::Sistema,
            new \DateTimeImmutable('now'),
        ));
    }

    private function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }
}

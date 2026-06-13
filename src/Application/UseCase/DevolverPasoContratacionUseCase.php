<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class DevolverPasoContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $expedienteId, string $pasoValue, string $nota): void
    {
        $nota = trim($nota);
        if (mb_strlen($nota) < 5) {
            throw new \InvalidArgumentException('Escriba una nota de al menos 5 caracteres para el cliente.');
        }

        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('El expediente no está en fase de contratación.');
        }

        $pasoEnum = PasoContratacionCliente::tryFrom($pasoValue)
            ?? throw new \InvalidArgumentException('Paso de contratación no válido.');

        $paso = $this->contratacionRepository->findPaso($id, $pasoEnum);
        if (null === $paso) {
            throw new \InvalidArgumentException('Paso no encontrado.');
        }

        if ($paso->estado() !== EstadoPasoContratacion::RealizadoCliente) {
            throw new \InvalidArgumentException('Solo puede devolver pasos pendientes de su revisión.');
        }

        $this->contratacionRepository->savePaso($paso->devolverConNota($nota));

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'paso_devuelto',
            sprintf('El abogado ha solicitado revisión del paso "%s": %s', $pasoEnum->label(), $nota),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
            $pasoEnum,
        ));

        $this->expedienteRepository->save(
            $expediente
                ->withEstadoFase(EstadoFaseExpediente::PendienteCliente)
                ->touchEstadoCambio(),
        );

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => 'paso_devuelto',
            'paso' => $pasoEnum->value,
            'nota' => $nota,
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }
}

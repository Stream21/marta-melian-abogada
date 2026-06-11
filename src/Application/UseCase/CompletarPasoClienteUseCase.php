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

final class CompletarPasoClienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $token, string $pasoValue): void
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Este expediente ya no está en fase de contratación.');
        }

        $pasoEnum = PasoContratacionCliente::tryFrom($pasoValue)
            ?? throw new \InvalidArgumentException('Paso de contratación no válido.');

        $expedienteId = $expediente->id();
        $paso = $this->contratacionRepository->findPaso($expedienteId, $pasoEnum);
        if (null === $paso) {
            throw new \InvalidArgumentException('Paso no encontrado.');
        }

        if ($paso->estado() !== EstadoPasoContratacion::Pendiente) {
            throw new \InvalidArgumentException('Este paso ya ha sido completado o está en revisión.');
        }

        $pasoActivo = $this->resolverPasoActivoCliente($expedienteId);
        if (null === $pasoActivo || $pasoActivo !== $pasoEnum) {
            throw new \InvalidArgumentException('Debe completar los pasos en orden y esperar la validación del abogado.');
        }

        $this->contratacionRepository->savePaso($paso->marcarRealizadoCliente());

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expedienteId,
            'paso_completado',
            sprintf('El cliente ha completado el paso "%s".', $pasoEnum->label()),
            ActorHitoExpediente::Cliente,
            new \DateTimeImmutable('now'),
            $pasoEnum,
        ));

        $this->actualizarEstadoExpediente($expedienteId);

        $this->realtime->publishContratacionUpdate($expedienteId->value(), [
            'type' => 'paso_completado',
            'paso' => $pasoEnum->value,
            'actor' => 'cliente',
        ]);
    }

    private function resolverPasoActivoCliente(ExpedienteId $expedienteId): ?PasoContratacionCliente
    {
        $pasos = $this->contratacionRepository->findPasosByExpediente($expedienteId);
        $porPaso = [];
        foreach ($pasos as $paso) {
            $porPaso[$paso->paso()->value] = $paso;
        }

        foreach (PasoContratacionCliente::ordenados() as $ordenPaso) {
            $paso = $porPaso[$ordenPaso->value] ?? null;
            if (null === $paso) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::ValidadoAbogado) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::RealizadoCliente) {
                return null;
            }

            return $ordenPaso;
        }

        return null;
    }

    private function actualizarEstadoExpediente(ExpedienteId $id): void
    {
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            return;
        }

        $pasos = $this->contratacionRepository->findPasosByExpediente($id);
        $hayPendienteValidacion = false;
        foreach ($pasos as $paso) {
            if ($paso->estado() === EstadoPasoContratacion::RealizadoCliente) {
                $hayPendienteValidacion = true;
                break;
            }
        }

        $nuevoEstado = $hayPendienteValidacion
            ? EstadoFaseExpediente::PendienteFirma
            : EstadoFaseExpediente::PendienteCliente;

        if ($expediente->estadoFase() !== $nuevoEstado) {
            $this->expedienteRepository->save($expediente->withEstadoFase($nuevoEstado));
        }
    }
}

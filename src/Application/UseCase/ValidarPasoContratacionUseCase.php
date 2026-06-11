<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Application\Port\ContratacionRealtimePort;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ValidarPasoContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
        private SincronizarClienteHoldedUseCase $sincronizarClienteHolded,
    ) {
    }

    public function __invoke(string $expedienteId, string $pasoValue): void
    {
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
            throw new \InvalidArgumentException('Este paso aún no ha sido completado por el cliente.');
        }

        $this->contratacionRepository->savePaso($paso->marcarValidadoAbogado());

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'paso_validado',
            sprintf('El abogado ha validado el paso "%s".', $pasoEnum->label()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
            $pasoEnum,
        ));

        $this->actualizarEstadoExpediente($id);

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => 'paso_validado',
            'paso' => $pasoEnum->value,
            'actor' => 'abogado',
        ]);
    }

    private function actualizarEstadoExpediente(ExpedienteId $id): void
    {
        $pasos = $this->contratacionRepository->findPasosByExpediente($id);
        $todosValidados = true;
        $hayPendienteCliente = false;

        foreach ($pasos as $paso) {
            if ($paso->estado() !== EstadoPasoContratacion::ValidadoAbogado) {
                $todosValidados = false;
            }
            if ($paso->estado() === EstadoPasoContratacion::Pendiente) {
                $hayPendienteCliente = true;
            }
        }

        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            return;
        }

        if ($todosValidados) {
            $this->expedienteRepository->save(
                $expediente
                    ->withFaseNegocio(FaseNegocioExpediente::Requerimientos, EstadoFaseExpediente::PendienteCliente)
                    ->withPaymentStatus('paid'),
            );

            $this->contratacionRepository->saveHito(new ExpedienteHito(
                bin2hex(random_bytes(16)),
                $id,
                'fase_completada',
                'Contratación completada. El expediente pasa a fase de requerimientos.',
                ActorHitoExpediente::Sistema,
                new \DateTimeImmutable('now'),
            ));

            $this->sincronizarClienteHoldedTrasContratacion($expediente->clienteId());

            return;
        }

        $nuevoEstado = $hayPendienteCliente
            ? EstadoFaseExpediente::PendienteCliente
            : EstadoFaseExpediente::PendienteFirma;

        if ($expediente->estadoFase() !== $nuevoEstado) {
            $this->expedienteRepository->save($expediente->withEstadoFase($nuevoEstado));
        }
    }

    private function sincronizarClienteHoldedTrasContratacion(?string $clienteId): void
    {
        if (null === $clienteId || '' === $clienteId) {
            return;
        }

        ($this->sincronizarClienteHolded)($clienteId, false);
    }
}

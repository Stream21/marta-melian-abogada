<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ManualPaymentRequest;
use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\CalendarioPagoService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;
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
        private CreateManualPaymentUseCase $createManualPayment,
        private CalendarioPagoService $calendarioPagoService,
        private InicializarRequerimientosUseCase $inicializarRequerimientos,
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

        $esPagoManualPendiente = PasoContratacionCliente::Pago === $pasoEnum
            && MetodoPagoExpediente::Manual === $expediente->metodoPago()
            && EstadoPasoContratacion::Pendiente === $paso->estado();

        if ($esPagoManualPendiente && !$this->pasoFirmasValidado($id)) {
            throw new \InvalidArgumentException('Debe validar las firmas antes de confirmar el pago.');
        }

        if (!$esPagoManualPendiente && EstadoPasoContratacion::RealizadoCliente !== $paso->estado()) {
            throw new \InvalidArgumentException('Este paso aún no ha sido completado por el cliente.');
        }

        if (PasoContratacionCliente::DatosCliente === $pasoEnum) {
            $this->sincronizarClienteHoldedTrasContratacion($expediente->clienteId());
        }

        if (PasoContratacionCliente::Pago === $pasoEnum && MetodoPagoExpediente::Manual === $expediente->metodoPago()) {
            $importeInicial = $this->calendarioPagoService->importePagoInicial(
                $expediente->honorariosAcordados(),
                $expediente->planPago(),
                $expediente->numCuotas(),
                $expediente->calendarioPagos(),
            );

            ($this->createManualPayment)(new ManualPaymentRequest(
                expedienteId: $expedienteId,
                amount: (string) $importeInicial,
                clientName: $expediente->clientName(),
                caseReference: $expediente->caseReference(),
            ));
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

        $faseCompletada = $this->actualizarEstadoExpediente($id);

        $payload = [
            'type' => 'paso_validado',
            'paso' => $pasoEnum->value,
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ];

        if ($faseCompletada) {
            $payload = [
                'type' => 'fase_completada',
                'faseNegocio' => FaseNegocioExpediente::Requerimientos->value,
                'actor' => 'sistema',
                'expedienteNumero' => $expediente->numero(),
                'clienteNombre' => $expediente->clientName(),
            ];
        }

        $this->realtime->publishContratacionUpdate($id->value(), $payload);
    }

    private function actualizarEstadoExpediente(ExpedienteId $id): bool
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
            return false;
        }

        if ($todosValidados) {
            $this->expedienteRepository->save(
                $expediente
                    ->withFaseNegocio(FaseNegocioExpediente::Requerimientos, EstadoFaseExpediente::RequerimientosEnProgreso)
                    ->withPaymentStatus('paid')
                    ->touchEstadoCambio(),
            );

            $this->contratacionRepository->saveHito(new ExpedienteHito(
                bin2hex(random_bytes(16)),
                $id,
                'fase_completada',
                'Contratación completada. El expediente pasa a fase de requerimientos.',
                ActorHitoExpediente::Sistema,
                new \DateTimeImmutable('now'),
            ));

            ($this->inicializarRequerimientos)($id);

            return true;
        }

        $nuevoEstado = $hayPendienteCliente
            ? EstadoFaseExpediente::PendienteCliente
            : EstadoFaseExpediente::PendienteFirma;

        if ($expediente->estadoFase() !== $nuevoEstado) {
            $this->expedienteRepository->save($expediente->withEstadoFase($nuevoEstado)->touchEstadoCambio());
        }

        return false;
    }

    private function pasoFirmasValidado(ExpedienteId $id): bool
    {
        $pasoFirmas = $this->contratacionRepository->findPaso($id, PasoContratacionCliente::Firmas);

        return null !== $pasoFirmas
            && EstadoPasoContratacion::ValidadoAbogado === $pasoFirmas->estado();
    }

    private function sincronizarClienteHoldedTrasContratacion(?string $clienteId): void
    {
        if (null === $clienteId || '' === $clienteId) {
            return;
        }

        ($this->sincronizarClienteHolded)($clienteId, false);
    }
}

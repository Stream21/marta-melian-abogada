<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\UseCase\ValidarPasoContratacionUseCase;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use Psr\Log\LoggerInterface;

final class FinalizarPagoStripeService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private PaymentHoldedSyncService $holdedSync,
        private CalendarioCobrosService $calendarioCobrosService,
        private ContratacionRealtimePort $realtime,
        private ValidarPasoContratacionUseCase $validarPasoContratacion,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $sessionMetadata
     */
    public function aplicar(
        Payment $payment,
        int $cuotaNumero,
        bool $notificarPagoRecibido = true,
        bool $registrarHitoPaso = true,
    ): ?Expediente {
        $expediente = $this->expedienteRepository->findById($payment->expedienteId());
        if (null === $expediente) {
            return null;
        }

        if (PaymentStatus::Paid !== $payment->status()) {
            $payment = $this->holdedSync->markPendingSync($payment->withStatus(PaymentStatus::Paid));
            $this->paymentRepository->save($payment);
        }

        $expediente = $this->sincronizarEstadoTrasPago($expediente, $payment, $cuotaNumero, $registrarHitoPaso);

        $this->attemptHoldedSync($payment, $expediente);

        if ($notificarPagoRecibido) {
            $this->realtime->publishContratacionUpdate($payment->expedienteId()->value(), [
                'type' => 'pago_recibido',
                'paymentId' => $payment->id()->value(),
                'cuotaNumero' => $cuotaNumero,
                'amount' => $payment->amount(),
                'actor' => 'cliente',
                'expedienteNumero' => $expediente->numero(),
                'clienteNombre' => $expediente->clientName(),
            ]);
        }

        return $expediente;
    }

    /**
     * @param array<string, mixed> $session
     */
    public function resolverCuotaNumero(array $session, Payment $payment): int
    {
        if (isset($session['metadata']['cuota_numero'])) {
            $cuota = (int) $session['metadata']['cuota_numero'];
            if ($cuota > 0) {
                return $cuota;
            }
        }

        if (null !== $payment->cuotaNumero() && $payment->cuotaNumero() > 0) {
            return $payment->cuotaNumero();
        }

        return 1;
    }

    private function sincronizarEstadoTrasPago(
        Expediente $expediente,
        Payment $payment,
        int $cuotaNumero,
        bool $registrarHitoPaso,
    ): Expediente {
        $calendario = $expediente->calendarioPagos();
        if ($cuotaNumero > 0) {
            $calendario = $this->calendarioCobrosService->marcarCuotaPagada($calendario, $cuotaNumero);
        }

        $expedienteActualizado = $expediente->withPaymentStatus('paid')->touchEstadoCambio();
        if (null !== $calendario) {
            $expedienteActualizado = $expedienteActualizado->withCalendarioPagos($calendario);
        }
        $this->expedienteRepository->save($expedienteActualizado);

        $pasoPago = $this->contratacionRepository->findPaso($payment->expedienteId(), PasoContratacionCliente::Pago);
        if (null !== $pasoPago && $pasoPago->estado() === EstadoPasoContratacion::Pendiente) {
            $this->contratacionRepository->savePaso($pasoPago->marcarRealizadoCliente());
            if ($registrarHitoPaso) {
                $this->contratacionRepository->saveHito(new ExpedienteHito(
                    bin2hex(random_bytes(16)),
                    $payment->expedienteId(),
                    'paso_completado',
                    'Pago digital confirmado vía Stripe.',
                    ActorHitoExpediente::Sistema,
                    new \DateTimeImmutable('now'),
                    PasoContratacionCliente::Pago,
                ));
            }
            $this->expedienteRepository->save(
                $expedienteActualizado->withEstadoFase(EstadoFaseExpediente::PendienteFirma)->touchEstadoCambio(),
            );
            if ($registrarHitoPaso) {
                $this->realtime->publishContratacionUpdate($payment->expedienteId()->value(), [
                    'type' => 'paso_completado',
                    'paso' => PasoContratacionCliente::Pago->value,
                    'actor' => 'sistema',
                ]);
            }
        }

        if (MetodoPagoExpediente::Digital === $expedienteActualizado->metodoPago()) {
            $pasoActual = $this->contratacionRepository->findPaso($payment->expedienteId(), PasoContratacionCliente::Pago);
            if (null !== $pasoActual && EstadoPasoContratacion::RealizadoCliente === $pasoActual->estado()) {
                try {
                    ($this->validarPasoContratacion)($expedienteActualizado->id()->value(), PasoContratacionCliente::Pago->value);
                } catch (\InvalidArgumentException $e) {
                    $this->logger->warning('Pago digital Stripe: no se pudo auto-validar el paso', [
                        'expedienteId' => $expedienteActualizado->id()->value(),
                        'paymentId' => $payment->id()->value(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $expedienteRefrescado = $this->expedienteRepository->findById($payment->expedienteId());

        return $expedienteRefrescado ?? $expedienteActualizado;
    }

    private function attemptHoldedSync(Payment $payment, Expediente $expediente): void
    {
        $result = $this->holdedSync->sync($payment, $expediente);
        $this->paymentRepository->save($result['payment']);

        if (!$result['success']) {
            $this->logger->warning('Pago Stripe: cobro registrado, Holded pendiente', [
                'paymentId' => $payment->id()->value(),
                'error' => $result['error'] ?? null,
            ]);
        }
    }
}

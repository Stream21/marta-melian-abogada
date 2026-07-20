<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\CalendarioCobrosService;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;

final class ObtenerFacturacionExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private CalendarioCobrosService $calendarioCobrosService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $payments = $this->paymentRepository->findByExpediente($id);
        $cobros = $this->calendarioCobrosService->listarCobros($expediente, $payments);
        $resumen = $this->calendarioCobrosService->resumen($cobros);

        $telefono = '';
        $email = '';
        if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $telefono = $cliente->telefono();
                $email = $cliente->email();
            }
        }

        $historial = [];
        $holdedPendientes = 0;
        $holdedErrores = 0;

        foreach ($payments as $payment) {
            if (PaymentStatus::Paid === $payment->status()) {
                if (PaymentHoldedEstado::PendienteSync === $payment->holdedEstado()) {
                    ++$holdedPendientes;
                } elseif (PaymentHoldedEstado::Error === $payment->holdedEstado()) {
                    ++$holdedErrores;
                }
            }

            $historial[] = [
                'id' => $payment->id()->value(),
                'status' => $payment->status()->value,
                'type' => $payment->type()->value,
                'amount' => $payment->amount(),
                'cuotaNumero' => $payment->cuotaNumero(),
                'holdedEstado' => $payment->holdedEstado()->value,
                'holdedEstadoLabel' => $payment->holdedEstado()->label(),
                'holdedSyncError' => $payment->holdedSyncError(),
                'pdfUrl' => $payment->invoicePdfUrl(),
                'createdAt' => $payment->createdAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return [
            'expedienteId' => $expediente->id()->value(),
            'numero' => $expediente->numero(),
            'clientName' => $expediente->clientName(),
            'honorariosAcordados' => $expediente->honorariosAcordados(),
            'planPago' => $expediente->planPago()->value,
            'numCuotas' => $expediente->numCuotas(),
            'metodoPago' => $expediente->metodoPago()->value,
            'contacto' => [
                'telefono' => $telefono,
                'email' => $email,
            ],
            'cobros' => $cobros,
            'resumen' => $resumen,
            'holdedResumen' => [
                'pendientes' => $holdedPendientes,
                'errores' => $holdedErrores,
                'requiereAccion' => ($holdedPendientes + $holdedErrores) > 0,
            ],
            'historialPagos' => $historial,
        ];
    }
}

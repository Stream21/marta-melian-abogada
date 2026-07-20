<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\ContratacionRealtimePort;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\Payment;
use App\Domain\Repository\ContratacionRepositoryInterface;

final class NotificarFalloSyncHoldedService
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function notificar(Expediente $expediente, Payment $payment, string $error): void
    {
        if (null !== $payment->invoicePdfUrl()) {
            return;
        }

        $hitoId = $this->hitoIdForPayment($payment->id()->value());
        if (null !== $this->contratacionRepository->findHitoById($hitoId)) {
            return;
        }

        $cuota = $payment->cuotaNumero();
        $descripcion = sprintf(
            'Cobro de %s € sin factura en Holded: sincronización fallida%s.',
            $payment->amount(),
            null !== $cuota ? ' (cuota ' . $cuota . ')' : '',
        );

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            $hitoId,
            $expediente->id(),
            'holded_sync_fallido',
            $descripcion,
            ActorHitoExpediente::Sistema,
            new \DateTimeImmutable('now'),
            null,
            $payment->id()->value(),
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'holded_sync_fallido',
            'paymentId' => $payment->id()->value(),
            'cuotaNumero' => $cuota,
            'amount' => $payment->amount(),
            'error' => $error,
            'actor' => 'sistema',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }

    private function hitoIdForPayment(string $paymentId): string
    {
        return 'holded-fail-' . $paymentId;
    }
}

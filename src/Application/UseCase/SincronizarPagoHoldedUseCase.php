<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\PaymentHoldedSyncService;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\PaymentId;

final class SincronizarPagoHoldedUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private PaymentHoldedSyncService $holdedSync,
    ) {
    }

    /**
     * @return array{success: bool, holdedInvoiceId?: string, error?: string}
     */
    public function __invoke(string $paymentId): array
    {
        $payment = $this->paymentRepository->findById(new PaymentId($paymentId));
        if (null === $payment) {
            throw new \InvalidArgumentException('Cobro no encontrado.');
        }

        if (PaymentStatus::Paid !== $payment->status()) {
            throw new \InvalidArgumentException('Solo se pueden sincronizar cobros ya pagados.');
        }

        if (!in_array($payment->holdedEstado(), [PaymentHoldedEstado::PendienteSync, PaymentHoldedEstado::Error], true)) {
            throw new \InvalidArgumentException('Este cobro no está pendiente de sincronización con Holded.');
        }

        $expediente = $this->expedienteRepository->findById($payment->expedienteId());
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $result = $this->holdedSync->sync($payment, $expediente, true);
        $this->paymentRepository->save($result['payment']);

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'No se pudo sincronizar con Holded.',
            ];
        }

        return [
            'success' => true,
            'holdedInvoiceId' => $result['payment']->holdedInvoiceId(),
        ];
    }
}

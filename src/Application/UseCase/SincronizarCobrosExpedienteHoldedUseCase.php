<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\PaymentHoldedSyncService;
use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class SincronizarCobrosExpedienteHoldedUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private PaymentHoldedSyncService $holdedSync,
    ) {
    }

    /**
     * @return array{
     *   success: bool,
     *   total: int,
     *   sincronizados: int,
     *   fallidos: int,
     *   errores: list<array{paymentId: string, cuotaNumero: int|null, error: string}>
     * }
     */
    public function __invoke(string $expedienteId): array
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $payments = $this->paymentRepository->findByExpediente(new ExpedienteId($expedienteId));
        $pendientes = array_values(array_filter(
            $payments,
            static fn ($payment) => PaymentStatus::Paid === $payment->status()
                && in_array($payment->holdedEstado(), [PaymentHoldedEstado::PendienteSync, PaymentHoldedEstado::Error], true),
        ));

        if ([] === $pendientes) {
            return [
                'success' => true,
                'total' => 0,
                'sincronizados' => 0,
                'fallidos' => 0,
                'errores' => [],
            ];
        }

        $sincronizados = 0;
        $errores = [];

        foreach ($pendientes as $payment) {
            $result = $this->holdedSync->sync($payment, $expediente, true);
            $this->paymentRepository->save($result['payment']);

            if ($result['success']) {
                ++$sincronizados;
                continue;
            }

            $errores[] = [
                'paymentId' => $payment->id()->value(),
                'cuotaNumero' => $payment->cuotaNumero(),
                'error' => $result['error'] ?? 'No se pudo sincronizar con Holded.',
            ];
        }

        $fallidos = count($errores);

        return [
            'success' => 0 === $fallidos,
            'total' => count($pendientes),
            'sincronizados' => $sincronizados,
            'fallidos' => $fallidos,
            'errores' => $errores,
        ];
    }
}

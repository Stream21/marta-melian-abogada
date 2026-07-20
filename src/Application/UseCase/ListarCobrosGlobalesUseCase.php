<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\PaymentHoldedEstado;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;

final class ListarCobrosGlobalesUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    /**
     * @param array{
     *   estadoCobro?: string,
     *   holdedEstado?: string,
     *   tipo?: string,
     *   desde?: string,
     *   hasta?: string,
     *   q?: string
     * } $filters
     *
     * @return array{
     *   items: list<array<string, mixed>>,
     *   kpis: array{cobradoMes: float, pendienteSyncHolded: int, stripePendientes: int}
     * }
     */
    public function __invoke(array $filters = []): array
    {
        $payments = $this->paymentRepository->findAll();
        $now = new \DateTimeImmutable('now');
        $mesInicio = $now->modify('first day of this month')->setTime(0, 0, 0);
        $mesFin = $now->modify('last day of this month')->setTime(23, 59, 59);

        $cobradoMes = 0.0;
        $pendienteSyncHolded = 0;
        $stripePendientes = 0;
        $items = [];

        foreach ($payments as $payment) {
            if (PaymentStatus::Paid === $payment->status()
                && $payment->createdAt() >= $mesInicio
                && $payment->createdAt() <= $mesFin
            ) {
                $cobradoMes += (float) $payment->amount();
            }

            if (in_array($payment->holdedEstado(), [PaymentHoldedEstado::PendienteSync, PaymentHoldedEstado::Error], true)) {
                ++$pendienteSyncHolded;
            }

            if (PaymentStatus::Pending === $payment->status() && PaymentType::Link === $payment->type()) {
                ++$stripePendientes;
            }

            if (!$this->matchesFilters($payment, $filters)) {
                continue;
            }

            $expediente = $this->expedienteRepository->findById($payment->expedienteId());
            $expedienteId = $payment->expedienteId()->value();
            $q = mb_strtolower(trim((string) ($filters['q'] ?? '')));
            if ('' !== $q && null !== $expediente) {
                $haystack = mb_strtolower(
                    ($expediente->numero() ?? '') . ' ' . $expediente->clientName() . ' ' . $expediente->caseReference(),
                );
                if (!str_contains($haystack, $q)) {
                    continue;
                }
            } elseif ('' !== $q && null === $expediente) {
                continue;
            }

            $items[] = [
                'id' => $payment->id()->value(),
                'expedienteId' => $expedienteId,
                'expedienteNumero' => $expediente?->numero() ?? '—',
                'clienteNombre' => $expediente?->clientName() ?? '—',
                'tramiteNombre' => $expediente?->caseReference() ?? '—',
                'amount' => $payment->amount(),
                'status' => $payment->status()->value,
                'statusLabel' => match ($payment->status()) {
                    PaymentStatus::Paid => 'Cobrado',
                    PaymentStatus::Pending => 'Pendiente',
                    PaymentStatus::Failed => 'Fallido',
                },
                'type' => $payment->type()->value,
                'typeLabel' => match ($payment->type()) {
                    PaymentType::Manual => 'Manual',
                    PaymentType::Link => 'Stripe',
                    PaymentType::Installment => 'Cuotas',
                },
                'holdedEstado' => $payment->holdedEstado()->value,
                'holdedEstadoLabel' => $payment->holdedEstado()->label(),
                'holdedSyncError' => $payment->holdedSyncError(),
                'holdedInvoiceId' => $payment->holdedInvoiceId(),
                'holdedSyncedAt' => $payment->holdedSyncedAt()?->format(\DateTimeInterface::ATOM),
                'stripeSessionId' => $payment->stripeSessionId(),
                'pdfUrl' => $payment->invoicePdfUrl(),
                'createdAt' => $payment->createdAt()->format(\DateTimeInterface::ATOM),
                'updatedAt' => $payment->updatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return [
            'items' => $items,
            'kpis' => [
                'cobradoMes' => round($cobradoMes, 2),
                'pendienteSyncHolded' => $pendienteSyncHolded,
                'stripePendientes' => $stripePendientes,
            ],
        ];
    }

    /**
     * @param array<string, string> $filters
     */
    private function matchesFilters(\App\Domain\Entity\Payment $payment, array $filters): bool
    {
        if (!$this->matchesMulti((string) ($filters['estadoCobro'] ?? ''), $payment->status()->value)) {
            return false;
        }

        if (!$this->matchesMulti((string) ($filters['holdedEstado'] ?? ''), $payment->holdedEstado()->value)) {
            return false;
        }

        if (!$this->matchesMulti((string) ($filters['tipo'] ?? ''), $payment->type()->value)) {
            return false;
        }

        $desde = trim((string) ($filters['desde'] ?? ''));
        if ('' !== $desde) {
            $desdeDt = \DateTimeImmutable::createFromFormat('Y-m-d', $desde);
            if ($desdeDt && $payment->createdAt() < $desdeDt->setTime(0, 0, 0)) {
                return false;
            }
        }

        $hasta = trim((string) ($filters['hasta'] ?? ''));
        if ('' !== $hasta) {
            $hastaDt = \DateTimeImmutable::createFromFormat('Y-m-d', $hasta);
            if ($hastaDt && $payment->createdAt() > $hastaDt->setTime(23, 59, 59)) {
                return false;
            }
        }

        return true;
    }

    private function matchesMulti(string $filterRaw, string $actual): bool
    {
        $filterRaw = trim($filterRaw);
        if ('' === $filterRaw) {
            return true;
        }

        $allowed = array_values(array_filter(array_map('trim', explode(',', $filterRaw))));

        return [] !== $allowed && in_array($actual, $allowed, true);
    }
}

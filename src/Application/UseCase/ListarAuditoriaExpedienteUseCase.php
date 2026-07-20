<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\ExpedienteAuditoriaCatalog;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ListarAuditoriaExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteAuditoriaCatalog $catalog,
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $items = [];

        foreach ($this->contratacionRepository->findHitosByExpediente($id) as $hito) {
            $tipo = $hito->tipo();
            $categoria = $this->catalog->categoriaForTipo($tipo);
            $canal = $this->catalog->canalForTipo($tipo, $hito->descripcion());

            $items[] = [
                'id' => $hito->id(),
                'source' => 'hito',
                'categoria' => $categoria,
                'categoriaLabel' => $this->catalog->categoriaLabel($categoria),
                'tipo' => $tipo,
                'tipoLabel' => $this->catalog->tipoLabel($tipo),
                'actor' => $hito->actor()->value,
                'actorLabel' => $this->catalog->actorLabel($hito->actor()->value),
                'resumen' => $this->resumenCorto($hito->descripcion()),
                'detalle' => $hito->descripcion(),
                'canal' => $canal,
                'canalLabel' => $this->catalog->canalLabel($canal),
                'paso' => $hito->paso()?->value,
                'referenciaId' => $hito->referenciaId(),
                'createdAt' => $hito->createdAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        foreach ($this->paymentRepository->findByExpediente($id) as $payment) {
            $tipo = 'pago_registrado';
            $categoria = 'pago';
            $importe = number_format((float) $payment->amount(), 2, ',', '.') . ' €';
            $tipoPago = match ($payment->type()) {
                PaymentType::Manual => 'manual',
                PaymentType::Link => 'Stripe',
                PaymentType::Installment => 'cuotas',
            };
            $estado = match ($payment->status()) {
                PaymentStatus::Paid => 'cobrado',
                PaymentStatus::Pending => 'pendiente',
                default => $payment->status()->value,
            };

            $detalle = sprintf(
                'Pago %s (%s) — %s — estado: %s.',
                $tipoPago,
                $importe,
                $payment->id()->value(),
                $estado,
            );

            $items[] = [
                'id' => 'payment-' . $payment->id()->value(),
                'source' => 'pago',
                'categoria' => $categoria,
                'categoriaLabel' => $this->catalog->categoriaLabel($categoria),
                'tipo' => $tipo,
                'tipoLabel' => $this->catalog->tipoLabel($tipo),
                'actor' => PaymentType::Manual === $payment->type() ? 'abogado' : 'cliente',
                'actorLabel' => PaymentType::Manual === $payment->type()
                    ? $this->catalog->actorLabel('abogado')
                    : $this->catalog->actorLabel('cliente'),
                'resumen' => sprintf('Cobro %s de %s (%s)', $tipoPago, $importe, $estado),
                'detalle' => $detalle,
                'canal' => PaymentType::Link === $payment->type() ? 'stripe' : null,
                'canalLabel' => PaymentType::Link === $payment->type() ? 'Stripe' : null,
                'paso' => 'pago',
                'createdAt' => $payment->updatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        $items[] = [
            'id' => 'expediente-apertura',
            'source' => 'expediente',
            'categoria' => 'estado',
            'categoriaLabel' => $this->catalog->categoriaLabel('estado'),
            'tipo' => 'expediente_aperturado',
            'tipoLabel' => 'Apertura expediente',
            'actor' => 'abogado',
            'actorLabel' => $this->catalog->actorLabel('abogado'),
            'resumen' => sprintf('Expediente %s abierto', $expediente->numero()),
            'detalle' => sprintf(
                'Expediente %s — %s — cliente: %s.',
                $expediente->numero(),
                $expediente->titulo(),
                $expediente->clientName(),
            ),
            'canal' => null,
            'canalLabel' => null,
            'paso' => null,
            'createdAt' => $expediente->fechaApertura()->format(\DateTimeInterface::ATOM),
        ];

        usort(
            $items,
            fn (array $a, array $b) => strcmp($b['createdAt'], $a['createdAt']),
        );

        return [
            'items' => $items,
            'total' => count($items),
        ];
    }

    private function resumenCorto(string $texto): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto) ?? $texto);

        return mb_strlen($texto) > 120 ? mb_substr($texto, 0, 117) . '…' : $texto;
    }
}

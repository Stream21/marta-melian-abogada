<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\HoldedPort;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class HandleStripeWebhookUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private HoldedPort $holdedPort,
        private string $stripeWebhookSecret,
    ) {
    }

    /**
     * Verifica la firma del payload y procesa el evento checkout.session.completed.
     * Retorna true si se procesó correctamente, false si la firma es inválida o no hay que procesar.
     */
    public function __invoke(string $payload, string $signature): bool
    {
        if ($this->stripeWebhookSecret === '' || !$this->verifySignature($payload, $signature)) {
            return false;
        }

        $data = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
        $type = $data['type'] ?? null;

        if ($type !== 'checkout.session.completed') {
            return true;
        }

        $session = $data['data']['object'] ?? [];
        $sessionId = $session['id'] ?? null;
        if (!$sessionId) {
            return true;
        }

        $payment = $this->paymentRepository->findByStripeSessionId($sessionId);
        if ($payment === null) {
            return true;
        }

        if ($payment->status() === PaymentStatus::Paid) {
            return true;
        }

        $expediente = $this->expedienteRepository->findById($payment->expedienteId());
        if ($expediente === null) {
            return true;
        }

        try {
            $holdedInvoiceId = $this->holdedPort->createInvoice([
                'client' => $expediente->clientName(),
                'reference' => $expediente->caseReference(),
                'amount' => $payment->amount(),
            ]);
            $this->holdedPort->markAsPaid($holdedInvoiceId);
        } catch (\Throwable $e) {
            return false;
        }

        $this->paymentRepository->save(new \App\Domain\Entity\Payment(
            $payment->id(),
            $payment->expedienteId(),
            PaymentStatus::Paid,
            $payment->type(),
            $holdedInvoiceId,
            $payment->stripeSessionId(),
            $payment->amount(),
            $payment->pdfPath(),
            $payment->createdAt(),
            new \DateTimeImmutable('now'),
        ));

        $expedienteUpdated = new \App\Domain\Entity\Expediente(
            $expediente->id(),
            $expediente->numero(),
            $expediente->titulo(),
            $expediente->estado(),
            $expediente->fechaApertura(),
            $expediente->clientName(),
            $expediente->caseReference(),
            $expediente->folderPath(),
            'paid',
        );
        $this->expedienteRepository->save($expedienteUpdated);

        return true;
    }

    private function verifySignature(string $payload, string $signature): bool
    {
        $elements = explode(',', $signature);
        $timestamp = null;
        $v1 = null;
        foreach ($elements as $element) {
            if (str_starts_with($element, 't=')) {
                $timestamp = substr($element, 2);
            }
            if (str_starts_with($element, 'v1=')) {
                $v1 = substr($element, 3);
            }
        }
        if ($timestamp === null || $v1 === null) {
            return false;
        }
        $signed = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signed, $this->stripeWebhookSecret);

        return hash_equals($expected, $v1);
    }
}

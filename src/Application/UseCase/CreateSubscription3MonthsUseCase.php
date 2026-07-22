<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Subscription3Request;
use App\Application\Port\StripePort;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;

final class CreateSubscription3MonthsUseCase
{
    public function __construct(
        private StripePort $stripePort,
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private string $frontendSuccessUrl = 'http://localhost:5173/payment/success',
        private string $frontendCancelUrl = 'http://localhost:5173/payment/cancel',
    ) {
    }

    /**
     * @return array{url: string, sessionId: string}|array{error: string}
     */
    public function __invoke(Subscription3Request $request): array
    {
        $expedienteId = new ExpedienteId($request->expedienteId);
        $expediente = $this->expedienteRepository->findById($expedienteId);

        if ($expediente === null) {
            return ['error' => 'Expediente no encontrado.'];
        }

        try {
            $amountCents = (string) (int) round((float) $request->amount * 100);
            $token = trim((string) $expediente->accessToken());
            $successUrl = $this->frontendSuccessUrl;
            $cancelUrl = $this->frontendCancelUrl;
            if ('' !== $token) {
                $q = 'token=' . rawurlencode($token);
                $successUrl .= (str_contains($successUrl, '?') ? '&' : '?') . $q;
                $cancelUrl .= (str_contains($cancelUrl, '?') ? '&' : '?') . $q;
            }

            $result = $this->stripePort->createSubscription3Months(
                $amountCents,
                $request->expedienteId,
                $successUrl,
                $cancelUrl,
            );

            $payment = new Payment(
                PaymentId::generate(),
                $expedienteId,
                PaymentStatus::Pending,
                PaymentType::Installment,
                null,
                $result['sessionId'],
                $request->amount,
                null,
                new \DateTimeImmutable('now'),
                new \DateTimeImmutable('now'),
            );
            $this->paymentRepository->save($payment);

            return [
                'url' => $result['url'],
                'sessionId' => $result['sessionId'],
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

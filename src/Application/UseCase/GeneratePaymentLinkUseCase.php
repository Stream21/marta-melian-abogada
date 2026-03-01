<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\GenerateLinkRequest;
use App\Application\DTO\GenerateLinkResult;
use App\Application\Port\StripePort;
use App\Application\Port\TwilioPort;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;

final class GeneratePaymentLinkUseCase
{
    public function __construct(
        private StripePort $stripePort,
        private TwilioPort $twilioPort,
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private string $frontendSuccessUrl = 'http://localhost:5173/payment/success',
        private string $frontendCancelUrl = 'http://localhost:5173/payment/cancel',
    ) {
    }

    public function __invoke(GenerateLinkRequest $request): GenerateLinkResult
    {
        $expedienteId = new ExpedienteId($request->expedienteId);
        $expediente = $this->expedienteRepository->findById($expedienteId);

        if ($expediente === null) {
            return GenerateLinkResult::failure('Expediente no encontrado.');
        }

        try {
            $amountCents = (string) (int) round((float) $request->amount * 100);

            $session = $this->stripePort->createCheckoutSession(
                $amountCents,
                $request->expedienteId,
                $this->frontendSuccessUrl,
                $this->frontendCancelUrl,
            );

            $payment = new Payment(
                PaymentId::generate(),
                $expedienteId,
                PaymentStatus::Pending,
                PaymentType::Link,
                null,
                $session['sessionId'],
                $request->amount,
                null,
                new \DateTimeImmutable('now'),
                new \DateTimeImmutable('now'),
            );
            $this->paymentRepository->save($payment);

            $message = 'Enlace de pago: ' . $session['url'];
            $this->twilioPort->sendWhatsAppMessage($request->phone, $message);

            return GenerateLinkResult::success($session['url'], $session['sessionId']);
        } catch (\Throwable $e) {
            return GenerateLinkResult::failure('No se pudo generar el enlace: ' . $e->getMessage());
        }
    }
}

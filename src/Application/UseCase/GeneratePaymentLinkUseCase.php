<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\GenerateLinkRequest;
use App\Application\DTO\GenerateLinkResult;
use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\EmailPort;
use App\Application\Port\StripePort;
use App\Application\Port\TwilioPort;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;

final class GeneratePaymentLinkUseCase
{
    public function __construct(
        private StripePort $stripePort,
        private TwilioPort $twilioPort,
        private EmailPort $emailPort,
        private PaymentRepositoryInterface $paymentRepository,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
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

        $phone = trim($request->phone);
        $email = trim($request->email);
        if ('' === $phone && '' === $email) {
            return GenerateLinkResult::failure('Indique teléfono (WhatsApp) o email del cliente.');
        }

        try {
            $amountCents = (string) (int) round((float) $request->amount * 100);
            $metadata = [];
            if (null !== $request->cuotaNumero) {
                $metadata['cuota_numero'] = (string) $request->cuotaNumero;
            }

            $session = $this->stripePort->createCheckoutSession(
                $amountCents,
                $request->expedienteId,
                $this->frontendSuccessUrl,
                $this->frontendCancelUrl,
                $metadata,
            );

            $now = new \DateTimeImmutable('now');
            $payment = new Payment(
                PaymentId::generate(),
                $expedienteId,
                PaymentStatus::Pending,
                PaymentType::Link,
                null,
                $session['sessionId'],
                $request->amount,
                null,
                $now,
                $now,
                Payment::defaultHoldedEstadoForType(PaymentType::Link),
                null,
                null,
                $request->cuotaNumero,
            );
            $this->paymentRepository->save($payment);

            $message = sprintf(
                "Enlace de pago del expediente %s (%s €):\n%s",
                $expediente->numero(),
                $request->amount,
                $session['url'],
            );

            if ('' !== $phone) {
                $this->twilioPort->sendWhatsAppMessage($phone, $message);
            }

            if ('' !== $email && $this->emailPort->isConfigured()) {
                $this->emailPort->send(
                    $email,
                    sprintf('Enlace de pago — Expediente %s', $expediente->numero()),
                    $message,
                );
            }

            $this->contratacionRepository->saveHito(new \App\Domain\Entity\ExpedienteHito(
                bin2hex(random_bytes(16)),
                $expedienteId,
                'enlace_pago_generado',
                sprintf(
                    'Enlace de pago Stripe generado%s.',
                    null !== $request->cuotaNumero ? ' para cuota ' . $request->cuotaNumero : '',
                ),
                \App\Domain\Entity\ActorHitoExpediente::Abogado,
                $now,
            ));

            $this->realtime->publishContratacionUpdate($request->expedienteId, [
                'type' => 'enlace_pago_generado',
                'paymentId' => $payment->id()->value(),
                'cuotaNumero' => $request->cuotaNumero,
                'amount' => $request->amount,
                'actor' => 'abogado',
            ]);

            return GenerateLinkResult::success($session['url'], $session['sessionId']);
        } catch (\Throwable $e) {
            return GenerateLinkResult::failure('No se pudo generar el enlace: ' . $e->getMessage());
        }
    }
}

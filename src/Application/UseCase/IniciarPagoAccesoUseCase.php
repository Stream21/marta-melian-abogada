<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\StripePort;
use App\Application\Service\CalendarioPagoService;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\PaymentId;

final class IniciarPagoAccesoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private PaymentRepositoryInterface $paymentRepository,
        private StripePort $stripePort,
        private CalendarioPagoService $calendarioPagoService,
        private string $frontendSuccessUrl,
        private string $frontendCancelUrl,
    ) {
    }

    /**
     * @return array{checkoutUrl: string}
     */
    public function __invoke(string $token): array
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Este expediente ya no está en fase de contratación.');
        }

        if ($expediente->metodoPago() !== MetodoPagoExpediente::Digital) {
            throw new \InvalidArgumentException('Este expediente no tiene pago digital configurado.');
        }

        $amount = $this->calendarioPagoService->importePagoInicial(
            $expediente->honorariosAcordados(),
            $expediente->planPago(),
            $expediente->numCuotas(),
            $expediente->calendarioPagos(),
        );
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Importe de pago no válido.');
        }

        $amountCents = (string) (int) round($amount * 100);
        $session = $this->stripePort->createCheckoutSession(
            $amountCents,
            $expediente->id()->value(),
            $this->frontendSuccessUrl,
            $this->frontendCancelUrl,
            ['cuota_numero' => '1'],
        );

        $now = new \DateTimeImmutable('now');
        $this->paymentRepository->save(new Payment(
            PaymentId::generate(),
            $expediente->id(),
            PaymentStatus::Pending,
            PaymentType::Link,
            null,
            $session['sessionId'],
            (string) $amount,
            null,
            $now,
            $now,
            Payment::defaultHoldedEstadoForType(PaymentType::Link),
            null,
            null,
            1,
        ));

        return ['checkoutUrl' => $session['url']];
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\ContratacionCompletitudValidator;
use App\Application\Service\FirmaOtpService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class VerificarOtpFirmaAccesoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionCompletitudValidator $completitudValidator,
        private FirmaOtpService $firmaOtpService,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $token, string $codigo): void
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        $pasoActivo = $this->completitudValidator->pasoActivoCliente($expediente->id());
        if (PasoContratacionCliente::Firmas !== $pasoActivo) {
            throw new \InvalidArgumentException('El paso de firmas no está disponible en este momento.');
        }

        if (null !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
            if (null === $tramite || !$tramite->requiereOtpFirma()) {
                throw new \InvalidArgumentException('Este trámite no requiere verificación OTP.');
            }
        }

        $this->firmaOtpService->verificar($expediente->id(), $codigo);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            'otp_firma_verificado',
            'Identidad verificada por SMS antes de firmar documentos legales.',
            ActorHitoExpediente::Cliente,
            new \DateTimeImmutable('now'),
            PasoContratacionCliente::Firmas,
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'otp_firma_verificado',
            'paso' => PasoContratacionCliente::Firmas->value,
            'actor' => 'cliente',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }
}

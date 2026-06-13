<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\CalendarioPagoService;
use App\Application\Service\ContratacionCompletitudValidator;
use App\Application\Service\DocumentoIntegridadService;
use App\Application\Service\FirmaOtpService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteFirmaDocumento;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class RegistrarFirmaDocumentoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private GenerarEscritoPdfUseCase $generarEscritoPdf,
        private ExpedienteFileStoragePort $fileStorage,
        private ContratacionCompletitudValidator $completitudValidator,
        private ContratacionRealtimePort $realtime,
        private DocumentoIntegridadService $integridadService,
        private FirmaOtpService $firmaOtpService,
        private TramiteRepositoryInterface $tramiteRepository,
        private CalendarioPagoService $calendarioPagoService,
    ) {
    }

    public function __invoke(
        string $token,
        string $tipo,
        string $firmaPngBinary,
        ?string $userAgent = null,
        ?string $clienteIp = null,
    ): void {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Este expediente ya no está en fase de contratación.');
        }

        $pasoActivo = $this->completitudValidator->pasoActivoCliente($expediente->id());
        if (PasoContratacionCliente::Firmas !== $pasoActivo) {
            throw new \InvalidArgumentException('El paso de firmas no está disponible en este momento.');
        }

        if ('' === $firmaPngBinary) {
            throw new \InvalidArgumentException('La firma es obligatoria.');
        }

        if ($this->tramiteRequiereOtp($expediente)) {
            $this->firmaOtpService->assertSesionVerificada($expediente->id());
        }

        $tipoEscrito = TipoEscrito::fromString($tipo);
        $firmadoAt = new \DateTimeImmutable('now');

        if (TipoEscrito::HojaEncargo === $tipoEscrito) {
            $calendario = $this->calendarioPagoService->calcular(
                $expediente->honorariosAcordados(),
                $expediente->planPago(),
                $expediente->numCuotas(),
                $firmadoAt,
            );
            $expediente = $expediente
                ->withFechaFirmaContrato($firmadoAt)
                ->withCalendarioPagos($calendario);
            $this->expedienteRepository->save($expediente);
        }

        $firmaPath = $this->fileStorage->savePdf(
            $expediente->id(),
            sprintf('firmas/%s_%s.png', $tipoEscrito->value, $firmadoAt->format('YmdHis')),
            $firmaPngBinary,
        );

        $result = ($this->generarEscritoPdf)(
            $expediente->id()->value(),
            $tipoEscrito->value,
            true,
            true,
            $firmaPath,
        );

        $userAgentNormalizado = $this->normalizarUserAgent($userAgent);
        $clienteIpNormalizada = $this->normalizarIp($clienteIp);
        $pdfBinary = $result['pdfBinary'] ?? '';
        if ('' === $pdfBinary) {
            throw new \RuntimeException('No se pudo generar el PDF firmado.');
        }
        $pdfSha256 = $this->integridadService->sha256Hex($pdfBinary);

        $firma = new ExpedienteFirmaDocumento(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            $tipoEscrito,
            $firmaPath,
            $result['pdfPath'],
            $firmadoAt,
            $userAgentNormalizado,
            $clienteIpNormalizada,
            $pdfSha256,
        );

        $this->firmaRepository->save($firma);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            'documento_firmado',
            $this->descripcionHitoFirma($tipoEscrito, $clienteIpNormalizada, $firmadoAt, $pdfSha256),
            ActorHitoExpediente::Cliente,
            $firmadoAt,
            PasoContratacionCliente::Firmas,
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'firma_registrada',
            'tipo' => $tipoEscrito->value,
            'actor' => 'cliente',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }

    private function descripcionHitoFirma(
        TipoEscrito $tipoEscrito,
        ?string $clienteIp,
        \DateTimeImmutable $firmadoAt,
        string $pdfSha256,
    ): string {
        $partes = [
            sprintf(
                'Firma electrónica simple de «%s» registrada a las %s.',
                $tipoEscrito->label(),
                $firmadoAt->format('d/m/Y H:i'),
            ),
        ];

        if (TipoEscrito::HojaEncargo === $tipoEscrito) {
            $partes[] = 'Calendario de pagos fijado con fecha de firma del contrato.';
        }

        if (null !== $clienteIp) {
            $partes[] = sprintf('IP: %s.', $clienteIp);
        }

        $partes[] = sprintf('Integridad SHA-256: %s.', $pdfSha256);

        $descripcion = implode(' ', $partes);

        return mb_strlen($descripcion) > 500 ? mb_substr($descripcion, 0, 497) . '…' : $descripcion;
    }

    private function normalizarUserAgent(?string $userAgent): ?string
    {
        if (null === $userAgent) {
            return null;
        }

        $userAgent = trim($userAgent);

        return '' === $userAgent ? null : mb_substr($userAgent, 0, 500);
    }

    private function normalizarIp(?string $ip): ?string
    {
        if (null === $ip) {
            return null;
        }

        $ip = trim($ip);

        if ('' === $ip || !filter_var($ip, \FILTER_VALIDATE_IP)) {
            return null;
        }

        return mb_substr($ip, 0, 45);
    }

    private function tramiteRequiereOtp(\App\Domain\Entity\Expediente $expediente): bool
    {
        if (null === $expediente->tramiteId()) {
            return true;
        }

        $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));

        return null === $tramite || $tramite->requiereOtpFirma();
    }
}

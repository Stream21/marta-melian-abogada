<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\ContratacionCompletitudValidator;
use App\Application\Service\FirmaOtpService;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class EnviarOtpFirmaAccesoUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private ContratacionCompletitudValidator $completitudValidator,
        private FirmaOtpService $firmaOtpService,
        private TelefonoNormalizer $telefonoNormalizer,
    ) {
    }

    /**
     * @return array{telefonoMascara: string, expiraEnSegundos: int, otpVerificado: bool}
     */
    public function __invoke(string $token): array
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        $this->assertFirmaDisponible($expediente->id());
        $this->assertOtpRequerido($expediente);

        if ($this->firmaOtpService->sesionVerificada($expediente->id())) {
            return [
                'telefonoMascara' => '',
                'expiraEnSegundos' => 0,
                'otpVerificado' => true,
            ];
        }

        $telefono = $this->resolverTelefonoCliente($expediente->clienteId());
        $resultado = $this->firmaOtpService->enviar($expediente->id(), $telefono);

        return [
            ...$resultado,
            'otpVerificado' => false,
        ];
    }

    private function assertFirmaDisponible(ExpedienteId $expedienteId): void
    {
        $pasoActivo = $this->completitudValidator->pasoActivoCliente($expedienteId);
        if (PasoContratacionCliente::Firmas !== $pasoActivo) {
            throw new \InvalidArgumentException('El paso de firmas no está disponible en este momento.');
        }
    }

    private function assertOtpRequerido(\App\Domain\Entity\Expediente $expediente): void
    {
        if (null === $expediente->tramiteId()) {
            throw new \InvalidArgumentException('Expediente sin trámite configurado.');
        }

        $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
        if (null === $tramite || !$tramite->requiereOtpFirma()) {
            throw new \InvalidArgumentException('Este trámite no requiere verificación OTP.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Este expediente ya no está en fase de contratación.');
        }
    }

    private function resolverTelefonoCliente(?string $clienteId): string
    {
        if (null === $clienteId || '' === $clienteId) {
            throw new \InvalidArgumentException('Expediente sin cliente vinculado.');
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        $telefono = $this->telefonoNormalizer->normalize($cliente->telefono());
        if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
            throw new \InvalidArgumentException('El cliente debe tener un teléfono móvil válido registrado.');
        }

        return $telefono;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Port\TwilioPort;
use App\Domain\Repository\FirmaOtpRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

/**
 * OTP de firma de documentos — exclusivamente por SMS.
 */
final class FirmaOtpService
{
    private const int CODIGO_LONGITUD = 6;
    private const int EXPIRACION_MINUTOS = 10;
    private const int SESION_MINUTOS = 30;
    private const int MAX_INTENTOS = 5;

    public function __construct(
        private FirmaOtpRepositoryInterface $otpRepository,
        private TwilioPort $twilioPort,
    ) {
    }

    /**
     * @return array{telefonoMascara: string, expiraEnSegundos: int}
     */
    public function enviar(ExpedienteId $expedienteId, string $telefono): array
    {
        $codigo = $this->generarCodigo();
        $expiresAt = (new \DateTimeImmutable())->modify('+' . self::EXPIRACION_MINUTOS . ' minutes');
        $hash = password_hash($codigo, \PASSWORD_DEFAULT);

        $this->otpRepository->savePending($expedienteId, $hash, $telefono, $expiresAt);

        $mensaje = sprintf(
            'Bufete Melián: su código para firmar documentos es %s. Válido %d minutos. No comparta este código.',
            $codigo,
            self::EXPIRACION_MINUTOS,
        );

        $this->twilioPort->sendSmsMessage($telefono, $mensaje);

        return [
            'telefonoMascara' => $this->mascaraTelefono($telefono),
            'expiraEnSegundos' => self::EXPIRACION_MINUTOS * 60,
        ];
    }

    public function verificar(ExpedienteId $expedienteId, string $codigo): void
    {
        $codigo = trim($codigo);
        if (!preg_match('/^\d{' . self::CODIGO_LONGITUD . '}$/', $codigo)) {
            throw new \InvalidArgumentException('El código debe tener 6 dígitos.');
        }

        $pending = $this->otpRepository->findLatestPending($expedienteId);
        if (null === $pending) {
            throw new \InvalidArgumentException('No hay código activo. Solicite uno nuevo.');
        }

        if ($pending['intentosFallidos'] >= self::MAX_INTENTOS) {
            throw new \InvalidArgumentException('Demasiados intentos fallidos. Solicite un código nuevo.');
        }

        $now = new \DateTimeImmutable();
        if ($pending['expiresAt'] < $now) {
            throw new \InvalidArgumentException('El código ha expirado. Solicite uno nuevo.');
        }

        if (!password_verify($codigo, $pending['codigoHash'])) {
            $this->otpRepository->incrementIntentos($pending['id']);
            throw new \InvalidArgumentException('Código incorrecto.');
        }

        $this->otpRepository->markVerified($pending['id'], $now);
    }

    public function sesionVerificada(ExpedienteId $expedienteId): bool
    {
        return $this->otpRepository->hasValidSession(
            $expedienteId,
            new \DateTimeImmutable(),
            self::SESION_MINUTOS,
        );
    }

    public function assertSesionVerificada(ExpedienteId $expedienteId): void
    {
        if (!$this->sesionVerificada($expedienteId)) {
            throw new \InvalidArgumentException('Debe verificar su identidad con el código SMS antes de firmar.');
        }
    }

    private function generarCodigo(): string
    {
        return str_pad((string) random_int(0, 10 ** self::CODIGO_LONGITUD - 1), self::CODIGO_LONGITUD, '0', STR_PAD_LEFT);
    }

    private function mascaraTelefono(string $telefono): string
    {
        $len = strlen($telefono);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($telefono, -4);
    }
}

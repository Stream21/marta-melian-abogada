<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\ValueObject\ExpedienteId;

interface FirmaOtpRepositoryInterface
{
    public function savePending(
        ExpedienteId $expedienteId,
        string $codigoHash,
        string $telefono,
        \DateTimeImmutable $expiresAt,
    ): string;

    public function findLatestPending(ExpedienteId $expedienteId): ?array;

    public function incrementIntentos(string $otpId): void;

    public function markVerified(string $otpId, \DateTimeImmutable $verifiedAt): void;

    public function hasValidSession(ExpedienteId $expedienteId, \DateTimeImmutable $now, int $sessionMinutes): bool;
}

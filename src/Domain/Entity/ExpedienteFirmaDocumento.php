<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;

final readonly class ExpedienteFirmaDocumento
{
    public function __construct(
        private string $id,
        private ExpedienteId $expedienteId,
        private TipoEscrito $tipoEscrito,
        private string $firmaPngPath,
        private ?string $pdfFirmadoPath,
        private \DateTimeImmutable $firmadoAt,
        private ?string $userAgent = null,
        private ?string $clienteIp = null,
        private ?string $pdfFirmadoSha256 = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function tipoEscrito(): TipoEscrito
    {
        return $this->tipoEscrito;
    }

    public function firmaPngPath(): string
    {
        return $this->firmaPngPath;
    }

    public function pdfFirmadoPath(): ?string
    {
        return $this->pdfFirmadoPath;
    }

    public function firmadoAt(): \DateTimeImmutable
    {
        return $this->firmadoAt;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function clienteIp(): ?string
    {
        return $this->clienteIp;
    }

    public function pdfFirmadoSha256(): ?string
    {
        return $this->pdfFirmadoSha256;
    }

    public function withPdfFirmadoPath(string $pdfFirmadoPath): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->tipoEscrito,
            $this->firmaPngPath,
            $pdfFirmadoPath,
            $this->firmadoAt,
            $this->userAgent,
            $this->clienteIp,
            $this->pdfFirmadoSha256,
        );
    }
}

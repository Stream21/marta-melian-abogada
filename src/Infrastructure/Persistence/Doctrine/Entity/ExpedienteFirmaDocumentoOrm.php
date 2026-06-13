<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_firma_documento')]
#[ORM\UniqueConstraint(name: 'UNIQ_EXP_FIRMA_TIPO', columns: ['expediente_id', 'tipo_escrito'])]
class ExpedienteFirmaDocumentoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $tipoEscrito;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $firmaPngPath;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $pdfFirmadoPath = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $firmadoAt;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $clienteIp = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $pdfFirmadoSha256 = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getExpedienteId(): string
    {
        return $this->expedienteId;
    }

    public function setExpedienteId(string $expedienteId): void
    {
        $this->expedienteId = $expedienteId;
    }

    public function getTipoEscrito(): string
    {
        return $this->tipoEscrito;
    }

    public function setTipoEscrito(string $tipoEscrito): void
    {
        $this->tipoEscrito = $tipoEscrito;
    }

    public function getFirmaPngPath(): string
    {
        return $this->firmaPngPath;
    }

    public function setFirmaPngPath(string $firmaPngPath): void
    {
        $this->firmaPngPath = $firmaPngPath;
    }

    public function getPdfFirmadoPath(): ?string
    {
        return $this->pdfFirmadoPath;
    }

    public function setPdfFirmadoPath(?string $pdfFirmadoPath): void
    {
        $this->pdfFirmadoPath = $pdfFirmadoPath;
    }

    public function getFirmadoAt(): \DateTimeImmutable
    {
        return $this->firmadoAt;
    }

    public function setFirmadoAt(\DateTimeImmutable $firmadoAt): void
    {
        $this->firmadoAt = $firmadoAt;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getClienteIp(): ?string
    {
        return $this->clienteIp;
    }

    public function setClienteIp(?string $clienteIp): void
    {
        $this->clienteIp = $clienteIp;
    }

    public function getPdfFirmadoSha256(): ?string
    {
        return $this->pdfFirmadoSha256;
    }

    public function setPdfFirmadoSha256(?string $pdfFirmadoSha256): void
    {
        $this->pdfFirmadoSha256 = $pdfFirmadoSha256;
    }
}

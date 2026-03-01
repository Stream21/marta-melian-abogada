<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente')]
class ExpedienteOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $numero;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $titulo;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $estado;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fechaApertura;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $clientName = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $caseReference = '';

    #[ORM\Column(type: Types::STRING, length: 500, options: ['default' => ''])]
    private string $folderPath = '';

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'pending'])]
    private string $paymentStatus = 'pending';

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): void
    {
        $this->numero = $numero;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): void
    {
        $this->titulo = $titulo;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    public function getFechaApertura(): \DateTimeImmutable
    {
        return $this->fechaApertura;
    }

    public function setFechaApertura(\DateTimeImmutable $fechaApertura): void
    {
        $this->fechaApertura = $fechaApertura;
    }

    public function getClientName(): string
    {
        return $this->clientName;
    }

    public function setClientName(string $clientName): void
    {
        $this->clientName = $clientName;
    }

    public function getCaseReference(): string
    {
        return $this->caseReference;
    }

    public function setCaseReference(string $caseReference): void
    {
        $this->caseReference = $caseReference;
    }

    public function getFolderPath(): string
    {
        return $this->folderPath;
    }

    public function setFolderPath(string $folderPath): void
    {
        $this->folderPath = $folderPath;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): void
    {
        $this->paymentStatus = $paymentStatus;
    }
}

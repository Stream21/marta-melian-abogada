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

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $clienteId = null;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $tramiteId = null;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $servicioId = null;

    #[ORM\Column(type: Types::STRING, length: 30, options: ['default' => 'contratacion'])]
    private string $faseNegocio = 'contratacion';

    #[ORM\Column(type: Types::STRING, length: 30, options: ['default' => 'pendiente_cliente'])]
    private string $estadoFase = 'pendiente_cliente';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0'])]
    private string $honorariosAcordados = '0';

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'manual'])]
    private string $metodoPago = 'manual';

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'unico'])]
    private string $planPago = 'unico';

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1])]
    private int $numCuotas = 1;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $accessToken = null;

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

    public function getClienteId(): ?string
    {
        return $this->clienteId;
    }

    public function setClienteId(?string $clienteId): void
    {
        $this->clienteId = $clienteId;
    }

    public function getTramiteId(): ?string
    {
        return $this->tramiteId;
    }

    public function setTramiteId(?string $tramiteId): void
    {
        $this->tramiteId = $tramiteId;
    }

    public function getServicioId(): ?string
    {
        return $this->servicioId;
    }

    public function setServicioId(?string $servicioId): void
    {
        $this->servicioId = $servicioId;
    }

    public function getFaseNegocio(): string
    {
        return $this->faseNegocio;
    }

    public function setFaseNegocio(string $faseNegocio): void
    {
        $this->faseNegocio = $faseNegocio;
    }

    public function getEstadoFase(): string
    {
        return $this->estadoFase;
    }

    public function setEstadoFase(string $estadoFase): void
    {
        $this->estadoFase = $estadoFase;
    }

    public function getHonorariosAcordados(): string
    {
        return $this->honorariosAcordados;
    }

    public function setHonorariosAcordados(string $honorariosAcordados): void
    {
        $this->honorariosAcordados = $honorariosAcordados;
    }

    public function getMetodoPago(): string
    {
        return $this->metodoPago;
    }

    public function setMetodoPago(string $metodoPago): void
    {
        $this->metodoPago = $metodoPago;
    }

    public function getPlanPago(): string
    {
        return $this->planPago;
    }

    public function setPlanPago(string $planPago): void
    {
        $this->planPago = $planPago;
    }

    public function getNumCuotas(): int
    {
        return $this->numCuotas;
    }

    public function setNumCuotas(int $numCuotas): void
    {
        $this->numCuotas = $numCuotas;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }
}

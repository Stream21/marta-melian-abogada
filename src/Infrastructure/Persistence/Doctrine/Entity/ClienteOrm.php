<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cliente')]
class ClienteOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nombre;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['default' => ''])]
    private string $nacionalidad = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => ''])]
    private string $tipoDocumento = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => ''])]
    private string $numDocumento = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $fechaNacimiento = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $lugarNacimiento = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => ''])]
    private string $estadoCivil = '';

    #[ORM\Column(type: Types::STRING, length: 500, options: ['default' => ''])]
    private string $domicilio = '';

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => ''])]
    private string $codigoPostal = '';

    #[ORM\Column(type: Types::STRING, length: 100, options: ['default' => ''])]
    private string $ciudad = '';

    #[ORM\Column(type: Types::STRING, length: 100, options: ['default' => ''])]
    private string $provincia = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $nombrePadre = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $nombreMadre = '';

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $email = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $holdedContactId = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'oportunidad'])]
    private string $holdedEstado = 'oportunidad';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $holdedSyncedAt = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $holdedSyncError = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $documentoIdentidadTipo = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $documentoIdentidadAnversoPath = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $documentoIdentidadReversoPath = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $documentoIdentidadEscaneadoAt = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getNacionalidad(): string
    {
        return $this->nacionalidad;
    }

    public function setNacionalidad(string $nacionalidad): void
    {
        $this->nacionalidad = $nacionalidad;
    }

    public function getTipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function setTipoDocumento(string $tipoDocumento): void
    {
        $this->tipoDocumento = $tipoDocumento;
    }

    public function getNumDocumento(): string
    {
        return $this->numDocumento;
    }

    public function setNumDocumento(string $numDocumento): void
    {
        $this->numDocumento = $numDocumento;
    }

    public function getFechaNacimiento(): ?\DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento(?\DateTimeImmutable $fechaNacimiento): void
    {
        $this->fechaNacimiento = $fechaNacimiento;
    }

    public function getLugarNacimiento(): string
    {
        return $this->lugarNacimiento;
    }

    public function setLugarNacimiento(string $lugarNacimiento): void
    {
        $this->lugarNacimiento = $lugarNacimiento;
    }

    public function getEstadoCivil(): string
    {
        return $this->estadoCivil;
    }

    public function setEstadoCivil(string $estadoCivil): void
    {
        $this->estadoCivil = $estadoCivil;
    }

    public function getDomicilio(): string
    {
        return $this->domicilio;
    }

    public function setDomicilio(string $domicilio): void
    {
        $this->domicilio = $domicilio;
    }

    public function getCodigoPostal(): string
    {
        return $this->codigoPostal;
    }

    public function setCodigoPostal(string $codigoPostal): void
    {
        $this->codigoPostal = $codigoPostal;
    }

    public function getCiudad(): string
    {
        return $this->ciudad;
    }

    public function setCiudad(string $ciudad): void
    {
        $this->ciudad = $ciudad;
    }

    public function getProvincia(): string
    {
        return $this->provincia;
    }

    public function setProvincia(string $provincia): void
    {
        $this->provincia = $provincia;
    }

    public function getNombrePadre(): string
    {
        return $this->nombrePadre;
    }

    public function setNombrePadre(string $nombrePadre): void
    {
        $this->nombrePadre = $nombrePadre;
    }

    public function getNombreMadre(): string
    {
        return $this->nombreMadre;
    }

    public function setNombreMadre(string $nombreMadre): void
    {
        $this->nombreMadre = $nombreMadre;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): void
    {
        $this->telefono = $telefono;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getHoldedContactId(): ?string
    {
        return $this->holdedContactId;
    }

    public function setHoldedContactId(?string $holdedContactId): void
    {
        $this->holdedContactId = $holdedContactId;
    }

    public function getHoldedEstado(): string
    {
        return $this->holdedEstado;
    }

    public function setHoldedEstado(string $holdedEstado): void
    {
        $this->holdedEstado = $holdedEstado;
    }

    public function getHoldedSyncedAt(): ?\DateTimeImmutable
    {
        return $this->holdedSyncedAt;
    }

    public function setHoldedSyncedAt(?\DateTimeImmutable $holdedSyncedAt): void
    {
        $this->holdedSyncedAt = $holdedSyncedAt;
    }

    public function getHoldedSyncError(): ?string
    {
        return $this->holdedSyncError;
    }

    public function setHoldedSyncError(?string $holdedSyncError): void
    {
        $this->holdedSyncError = $holdedSyncError;
    }

    public function getDocumentoIdentidadTipo(): ?string
    {
        return $this->documentoIdentidadTipo;
    }

    public function setDocumentoIdentidadTipo(?string $documentoIdentidadTipo): void
    {
        $this->documentoIdentidadTipo = $documentoIdentidadTipo;
    }

    public function getDocumentoIdentidadAnversoPath(): ?string
    {
        return $this->documentoIdentidadAnversoPath;
    }

    public function setDocumentoIdentidadAnversoPath(?string $documentoIdentidadAnversoPath): void
    {
        $this->documentoIdentidadAnversoPath = $documentoIdentidadAnversoPath;
    }

    public function getDocumentoIdentidadReversoPath(): ?string
    {
        return $this->documentoIdentidadReversoPath;
    }

    public function setDocumentoIdentidadReversoPath(?string $documentoIdentidadReversoPath): void
    {
        $this->documentoIdentidadReversoPath = $documentoIdentidadReversoPath;
    }

    public function getDocumentoIdentidadEscaneadoAt(): ?\DateTimeImmutable
    {
        return $this->documentoIdentidadEscaneadoAt;
    }

    public function setDocumentoIdentidadEscaneadoAt(?\DateTimeImmutable $documentoIdentidadEscaneadoAt): void
    {
        $this->documentoIdentidadEscaneadoAt = $documentoIdentidadEscaneadoAt;
    }
}

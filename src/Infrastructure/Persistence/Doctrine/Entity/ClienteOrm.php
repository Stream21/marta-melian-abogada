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

    #[ORM\Column(type: Types::STRING, length: 500, options: ['default' => ''])]
    private string $domicilio = '';

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => ''])]
    private string $codigoPostal = '';

    #[ORM\Column(type: Types::STRING, length: 100, options: ['default' => ''])]
    private string $ciudad = '';

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $email = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

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
}

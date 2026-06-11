<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'despacho_config')]
class DespachoConfigOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nombreFirma;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nombreLetrada;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $numColegiado;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $direccion;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $ciudad;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $subtituloProfesional = '';

    #[ORM\Column(type: Types::STRING, length: 50, options: ['default' => ''])]
    private string $telefono = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $email = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $web = '';

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => ''])]
    private string $nif = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $colegioAbogados = '';

    #[ORM\Column(type: Types::STRING, length: 34, options: ['default' => ''])]
    private string $iban = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $entidadBancaria = '';

    #[ORM\Column(type: Types::STRING, length: 255, options: ['default' => ''])]
    private string $titularCuenta = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cabeceraHtml = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pieHtml = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $selloPath = null;

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

    public function getNombreFirma(): string
    {
        return $this->nombreFirma;
    }

    public function setNombreFirma(string $nombreFirma): void
    {
        $this->nombreFirma = $nombreFirma;
    }

    public function getNombreLetrada(): string
    {
        return $this->nombreLetrada;
    }

    public function setNombreLetrada(string $nombreLetrada): void
    {
        $this->nombreLetrada = $nombreLetrada;
    }

    public function getNumColegiado(): string
    {
        return $this->numColegiado;
    }

    public function setNumColegiado(string $numColegiado): void
    {
        $this->numColegiado = $numColegiado;
    }

    public function getDireccion(): string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): void
    {
        $this->direccion = $direccion;
    }

    public function getCiudad(): string
    {
        return $this->ciudad;
    }

    public function setCiudad(string $ciudad): void
    {
        $this->ciudad = $ciudad;
    }

    public function getSubtituloProfesional(): string
    {
        return $this->subtituloProfesional;
    }

    public function setSubtituloProfesional(string $subtituloProfesional): void
    {
        $this->subtituloProfesional = $subtituloProfesional;
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): void
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

    public function getWeb(): string
    {
        return $this->web;
    }

    public function setWeb(string $web): void
    {
        $this->web = $web;
    }

    public function getNif(): string
    {
        return $this->nif;
    }

    public function setNif(string $nif): void
    {
        $this->nif = $nif;
    }

    public function getColegioAbogados(): string
    {
        return $this->colegioAbogados;
    }

    public function setColegioAbogados(string $colegioAbogados): void
    {
        $this->colegioAbogados = $colegioAbogados;
    }

    public function getIban(): string
    {
        return $this->iban;
    }

    public function setIban(string $iban): void
    {
        $this->iban = $iban;
    }

    public function getEntidadBancaria(): string
    {
        return $this->entidadBancaria;
    }

    public function setEntidadBancaria(string $entidadBancaria): void
    {
        $this->entidadBancaria = $entidadBancaria;
    }

    public function getTitularCuenta(): string
    {
        return $this->titularCuenta;
    }

    public function setTitularCuenta(string $titularCuenta): void
    {
        $this->titularCuenta = $titularCuenta;
    }

    public function getCabeceraHtml(): ?string
    {
        return $this->cabeceraHtml;
    }

    public function setCabeceraHtml(?string $cabeceraHtml): void
    {
        $this->cabeceraHtml = $cabeceraHtml;
    }

    public function getPieHtml(): ?string
    {
        return $this->pieHtml;
    }

    public function setPieHtml(?string $pieHtml): void
    {
        $this->pieHtml = $pieHtml;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): void
    {
        $this->logoPath = $logoPath;
    }

    public function getSelloPath(): ?string
    {
        return $this->selloPath;
    }

    public function setSelloPath(?string $selloPath): void
    {
        $this->selloPath = $selloPath;
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

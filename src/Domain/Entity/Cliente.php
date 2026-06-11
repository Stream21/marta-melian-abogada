<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ClienteId;

final readonly class Cliente
{
    public function __construct(
        private ClienteId $id,
        private string $nombre,
        private string $nacionalidad = '',
        private string $tipoDocumento = '',
        private string $numDocumento = '',
        private ?\DateTimeImmutable $fechaNacimiento = null,
        private string $lugarNacimiento = '',
        private string $domicilio = '',
        private string $codigoPostal = '',
        private string $ciudad = '',
        private string $telefono = '',
        private string $email = '',
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): ClienteId
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function nacionalidad(): string
    {
        return $this->nacionalidad;
    }

    public function tipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function numDocumento(): string
    {
        return $this->numDocumento;
    }

    public function fechaNacimiento(): ?\DateTimeImmutable
    {
        return $this->fechaNacimiento;
    }

    public function lugarNacimiento(): string
    {
        return $this->lugarNacimiento;
    }

    public function domicilio(): string
    {
        return $this->domicilio;
    }

    public function domicilioCompleto(): string
    {
        $parts = array_filter([$this->domicilio, $this->codigoPostal, $this->ciudad]);

        return implode(', ', $parts);
    }

    public function codigoPostal(): string
    {
        return $this->codigoPostal;
    }

    public function ciudad(): string
    {
        return $this->ciudad;
    }

    public function telefono(): string
    {
        return $this->telefono;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withDatos(
        string $nombre,
        string $nacionalidad,
        string $tipoDocumento,
        string $numDocumento,
        ?\DateTimeImmutable $fechaNacimiento,
        string $lugarNacimiento,
        string $domicilio,
        string $codigoPostal,
        string $ciudad,
        string $telefono,
        string $email,
    ): self {
        return new self(
            $this->id,
            $nombre,
            $nacionalidad,
            $tipoDocumento,
            $numDocumento,
            $fechaNacimiento,
            $lugarNacimiento,
            $domicilio,
            $codigoPostal,
            $ciudad,
            $telefono,
            $email,
            $this->createdAt,
            $this->updatedAt,
        );
    }
}

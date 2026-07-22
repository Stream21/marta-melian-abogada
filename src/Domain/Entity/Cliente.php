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
        private string $estadoCivil = '',
        private string $domicilio = '',
        private string $codigoPostal = '',
        private string $ciudad = '',
        private string $provincia = '',
        private string $nombrePadre = '',
        private string $nombreMadre = '',
        private string $telefono = '',
        private string $email = '',
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
        private ?string $holdedContactId = null,
        private ClienteHoldedEstado $holdedEstado = ClienteHoldedEstado::Oportunidad,
        private ?\DateTimeImmutable $holdedSyncedAt = null,
        private ?string $holdedSyncError = null,
        private ?TipoEscaneoDocumentoIdentidad $documentoIdentidadTipo = null,
        private ?string $documentoIdentidadAnversoPath = null,
        private ?string $documentoIdentidadReversoPath = null,
        private ?\DateTimeImmutable $documentoIdentidadEscaneadoAt = null,
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

    public function estadoCivil(): string
    {
        return $this->estadoCivil;
    }

    public function domicilio(): string
    {
        return $this->domicilio;
    }

    public function domicilioCompleto(): string
    {
        $parts = array_filter([$this->domicilio, $this->codigoPostal, $this->ciudad, $this->provincia]);

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

    public function provincia(): string
    {
        return $this->provincia;
    }

    public function nombrePadre(): string
    {
        return $this->nombrePadre;
    }

    public function nombreMadre(): string
    {
        return $this->nombreMadre;
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

    public function holdedContactId(): ?string
    {
        return $this->holdedContactId;
    }

    public function holdedEstado(): ClienteHoldedEstado
    {
        return $this->holdedEstado;
    }

    public function holdedSyncedAt(): ?\DateTimeImmutable
    {
        return $this->holdedSyncedAt;
    }

    public function holdedSyncError(): ?string
    {
        return $this->holdedSyncError;
    }

    public function estaSincronizadoHolded(): bool
    {
        return $this->holdedEstado === ClienteHoldedEstado::Sincronizado && null !== $this->holdedContactId;
    }

    public function documentoIdentidadTipo(): ?TipoEscaneoDocumentoIdentidad
    {
        return $this->documentoIdentidadTipo;
    }

    public function documentoIdentidadAnversoPath(): ?string
    {
        return $this->documentoIdentidadAnversoPath;
    }

    public function documentoIdentidadReversoPath(): ?string
    {
        return $this->documentoIdentidadReversoPath;
    }

    public function documentoIdentidadEscaneadoAt(): ?\DateTimeImmutable
    {
        return $this->documentoIdentidadEscaneadoAt;
    }

    public function tieneDocumentoIdentidad(): bool
    {
        return null !== $this->documentoIdentidadAnversoPath && '' !== $this->documentoIdentidadAnversoPath;
    }

    public function withDocumentoIdentidad(
        TipoEscaneoDocumentoIdentidad $tipo,
        string $anversoPath,
        ?string $reversoPath,
    ): self {
        return new self(
            $this->id,
            $this->nombre,
            $this->nacionalidad,
            $this->tipoDocumento,
            $this->numDocumento,
            $this->fechaNacimiento,
            $this->lugarNacimiento,
            $this->estadoCivil,
            $this->domicilio,
            $this->codigoPostal,
            $this->ciudad,
            $this->provincia,
            $this->nombrePadre,
            $this->nombreMadre,
            $this->telefono,
            $this->email,
            $this->createdAt,
            $this->updatedAt,
            $this->holdedContactId,
            $this->holdedEstado,
            $this->holdedSyncedAt,
            $this->holdedSyncError,
            $tipo,
            $anversoPath,
            $reversoPath,
            new \DateTimeImmutable('now'),
        );
    }

    public function withDatos(
        string $nombre,
        string $nacionalidad,
        string $tipoDocumento,
        string $numDocumento,
        ?\DateTimeImmutable $fechaNacimiento,
        string $lugarNacimiento,
        string $estadoCivil,
        string $domicilio,
        string $codigoPostal,
        string $ciudad,
        string $provincia,
        string $nombrePadre,
        string $nombreMadre,
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
            $estadoCivil,
            $domicilio,
            $codigoPostal,
            $ciudad,
            $provincia,
            $nombrePadre,
            $nombreMadre,
            $telefono,
            $email,
            $this->createdAt,
            $this->updatedAt,
            $this->holdedContactId,
            $this->holdedEstado,
            $this->holdedSyncedAt,
            $this->holdedSyncError,
            $this->documentoIdentidadTipo,
            $this->documentoIdentidadAnversoPath,
            $this->documentoIdentidadReversoPath,
            $this->documentoIdentidadEscaneadoAt,
        );
    }

    /** Vacía el teléfono (p. ej. para reasignarlo a otro cliente manteniendo la unicidad en BD). */
    public function withoutTelefono(): self
    {
        return $this->withDatos(
            $this->nombre,
            $this->nacionalidad,
            $this->tipoDocumento,
            $this->numDocumento,
            $this->fechaNacimiento,
            $this->lugarNacimiento,
            $this->estadoCivil,
            $this->domicilio,
            $this->codigoPostal,
            $this->ciudad,
            $this->provincia,
            $this->nombrePadre,
            $this->nombreMadre,
            '',
            $this->email,
        );
    }

    public function withHoldedSincronizado(string $holdedContactId): self
    {
        return new self(
            $this->id,
            $this->nombre,
            $this->nacionalidad,
            $this->tipoDocumento,
            $this->numDocumento,
            $this->fechaNacimiento,
            $this->lugarNacimiento,
            $this->estadoCivil,
            $this->domicilio,
            $this->codigoPostal,
            $this->ciudad,
            $this->provincia,
            $this->nombrePadre,
            $this->nombreMadre,
            $this->telefono,
            $this->email,
            $this->createdAt,
            $this->updatedAt,
            $holdedContactId,
            ClienteHoldedEstado::Sincronizado,
            new \DateTimeImmutable('now'),
            null,
            $this->documentoIdentidadTipo,
            $this->documentoIdentidadAnversoPath,
            $this->documentoIdentidadReversoPath,
            $this->documentoIdentidadEscaneadoAt,
        );
    }

    public function withHoldedError(string $error): self
    {
        return new self(
            $this->id,
            $this->nombre,
            $this->nacionalidad,
            $this->tipoDocumento,
            $this->numDocumento,
            $this->fechaNacimiento,
            $this->lugarNacimiento,
            $this->estadoCivil,
            $this->domicilio,
            $this->codigoPostal,
            $this->ciudad,
            $this->provincia,
            $this->nombrePadre,
            $this->nombreMadre,
            $this->telefono,
            $this->email,
            $this->createdAt,
            $this->updatedAt,
            $this->holdedContactId,
            ClienteHoldedEstado::Error,
            $this->holdedSyncedAt,
            self::truncarTexto($error, 500),
            $this->documentoIdentidadTipo,
            $this->documentoIdentidadAnversoPath,
            $this->documentoIdentidadReversoPath,
            $this->documentoIdentidadEscaneadoAt,
        );
    }

    /** Ajusta textos a columnas varchar de la BD (p. ej. holded_sync_error). */
    private static function truncarTexto(string $texto, int $max): string
    {
        $limpio = trim(preg_replace('/\s+/u', ' ', strip_tags($texto)) ?? $texto);
        if (mb_strlen($limpio) <= $max) {
            return $limpio;
        }

        return mb_substr($limpio, 0, $max - 1) . '…';
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final readonly class ExpedienteRequerimientoMercurio
{
    public function __construct(
        private ExpedienteRequerimientoMercurioId $id,
        private ExpedienteId $expedienteId,
        private TipoRequerimientoMercurio $tipo,
        private DestinoRequerimientoMercurio $destino,
        private string $nombre,
        private string $descripcion,
        private EstadoRequerimientoMercurio $estado,
        private ?string $archivoPath = null,
        private ?string $archivoNombre = null,
        private ?string $justificantePresentacionPath = null,
        private ?string $formularioNombre = null,
        private ?string $formularioCometido = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('now'),
        private \DateTimeImmutable $updatedAt = new \DateTimeImmutable('now'),
    ) {
        if ('' === trim($nombre)) {
            throw new \InvalidArgumentException('El nombre del requerimiento es obligatorio.');
        }
    }

    public static function crear(
        ExpedienteRequerimientoMercurioId $id,
        ExpedienteId $expedienteId,
        TipoRequerimientoMercurio $tipo,
        DestinoRequerimientoMercurio $destino,
        string $nombre,
        string $descripcion = '',
    ): self {
        $estado = DestinoRequerimientoMercurio::Cliente === $destino
            ? EstadoRequerimientoMercurio::PendienteCliente
            : EstadoRequerimientoMercurio::PendienteDespacho;

        return new self(
            $id,
            $expedienteId,
            $tipo,
            $destino,
            trim($nombre),
            trim($descripcion),
            $estado,
        );
    }

    public function id(): ExpedienteRequerimientoMercurioId
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function tipo(): TipoRequerimientoMercurio
    {
        return $this->tipo;
    }

    public function destino(): DestinoRequerimientoMercurio
    {
        return $this->destino;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function estado(): EstadoRequerimientoMercurio
    {
        return $this->estado;
    }

    public function archivoPath(): ?string
    {
        return $this->archivoPath;
    }

    public function archivoNombre(): ?string
    {
        return $this->archivoNombre;
    }

    public function justificantePresentacionPath(): ?string
    {
        return $this->justificantePresentacionPath;
    }

    public function formularioNombre(): ?string
    {
        return $this->formularioNombre;
    }

    public function formularioCometido(): ?string
    {
        return $this->formularioCometido;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withFormularioMeta(?string $nombre, ?string $cometido): self
    {
        $nombreTrim = null !== $nombre ? trim($nombre) : null;
        $cometidoTrim = null !== $cometido ? trim($cometido) : null;

        return new self(
            $this->id,
            $this->expedienteId,
            $this->tipo,
            $this->destino,
            $this->nombre,
            $this->descripcion,
            $this->estado,
            $this->archivoPath,
            $this->archivoNombre,
            $this->justificantePresentacionPath,
            '' !== ($nombreTrim ?? '') ? $nombreTrim : null,
            '' !== ($cometidoTrim ?? '') ? $cometidoTrim : null,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }

    public function withArchivo(string $path, string $nombre): self
    {
        $estado = DestinoRequerimientoMercurio::Cliente === $this->destino
            && EstadoRequerimientoMercurio::PendienteCliente === $this->estado
            ? EstadoRequerimientoMercurio::PendienteDespacho
            : $this->estado;

        return new self(
            $this->id,
            $this->expedienteId,
            $this->tipo,
            $this->destino,
            $this->nombre,
            $this->descripcion,
            $estado,
            $path,
            $nombre,
            $this->justificantePresentacionPath,
            $this->formularioNombre,
            $this->formularioCometido,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }

    public function marcarPresentado(
        string $justificantePath,
        ?string $archivoPath = null,
        ?string $archivoNombre = null,
    ): self {
        $path = $archivoPath ?? $this->archivoPath;
        $nombre = $archivoNombre ?? $this->archivoNombre;
        if (null === $path || '' === trim($path)) {
            throw new \InvalidArgumentException('Debe adjuntar el documento o seleccionar un escrito antes de presentar.');
        }
        if ('' === trim($justificantePath)) {
            throw new \InvalidArgumentException('Debe adjuntar el justificante de presentación en Mercurio.');
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->tipo,
            $this->destino,
            $this->nombre,
            $this->descripcion,
            EstadoRequerimientoMercurio::Presentado,
            $path,
            $nombre,
            $justificantePath,
            $this->formularioNombre,
            $this->formularioCometido,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }

    public function cerrar(): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->tipo,
            $this->destino,
            $this->nombre,
            $this->descripcion,
            EstadoRequerimientoMercurio::Presentado,
            $this->archivoPath,
            $this->archivoNombre,
            $this->justificantePresentacionPath,
            $this->formularioNombre,
            $this->formularioCometido,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }

    public function withEstado(EstadoRequerimientoMercurio $estado): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->tipo,
            $this->destino,
            $this->nombre,
            $this->descripcion,
            $estado,
            $this->archivoPath,
            $this->archivoNombre,
            $this->justificantePresentacionPath,
            $this->formularioNombre,
            $this->formularioCometido,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }

    /**
     * Expone el requerimiento al portal del cliente (documentos/campos para el cliente).
     */
    public function abrirParaCliente(): self
    {
        if (!$this->estado->estaAbierto()) {
            return $this;
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->tipo,
            DestinoRequerimientoMercurio::Cliente,
            $this->nombre,
            $this->descripcion,
            EstadoRequerimientoMercurio::PendienteCliente,
            $this->archivoPath,
            $this->archivoNombre,
            $this->justificantePresentacionPath,
            $this->formularioNombre,
            $this->formularioCometido,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }

    /**
     * Presentación en Mercurio (PDF de presentación + justificante) tras completar ítems del requerimiento.
     */
    public function marcarPresentadoEnMercurio(
        string $presentacionPath,
        string $presentacionNombre,
        string $justificantePath,
    ): self {
        if ('' === trim($presentacionPath)) {
            throw new \InvalidArgumentException('Debe adjuntar el documento de presentación en Mercurio.');
        }
        if ('' === trim($justificantePath)) {
            throw new \InvalidArgumentException('Debe adjuntar el justificante de presentación en Mercurio.');
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->tipo,
            $this->destino,
            $this->nombre,
            $this->descripcion,
            EstadoRequerimientoMercurio::Presentado,
            $presentacionPath,
            $presentacionNombre,
            $justificantePath,
            $this->formularioNombre,
            $this->formularioCometido,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }
}

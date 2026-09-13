<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final readonly class ExpedienteRequerimientoDocumento
{
    public function __construct(
        private ExpedienteRequerimientoDocumentoId $id,
        private ExpedienteRequerimientoMercurioId $requerimientoId,
        private string $nombre,
        private string $descripcion,
        private ResponsableRequerimientoDocumento $responsable,
        private bool $obligatorio,
        private EstadoRequerimientoDocumento $estado,
        private int $orden,
        private ?string $archivoPath = null,
        private ?string $notaRechazo = null,
        private int $maxArchivos = 1,
    ) {
        if ('' === trim($nombre)) {
            throw new \InvalidArgumentException('El nombre del documento es obligatorio.');
        }
        if ($maxArchivos < 1 || $maxArchivos > 20) {
            throw new \InvalidArgumentException('El máximo de ficheros debe estar entre 1 y 20.');
        }
    }

    public static function crear(
        ExpedienteRequerimientoDocumentoId $id,
        ExpedienteRequerimientoMercurioId $requerimientoId,
        string $nombre,
        string $descripcion,
        ResponsableRequerimientoDocumento $responsable,
        bool $obligatorio,
        int $orden,
        int $maxArchivos = 1,
    ): self {
        return new self(
            $id,
            $requerimientoId,
            trim($nombre),
            trim($descripcion),
            $responsable,
            $obligatorio,
            EstadoRequerimientoDocumento::Pendiente,
            $orden,
            null,
            null,
            max(1, min(20, $maxArchivos)),
        );
    }

    public function id(): ExpedienteRequerimientoDocumentoId
    {
        return $this->id;
    }

    public function requerimientoId(): ExpedienteRequerimientoMercurioId
    {
        return $this->requerimientoId;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function responsable(): ResponsableRequerimientoDocumento
    {
        return $this->responsable;
    }

    public function obligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function estado(): EstadoRequerimientoDocumento
    {
        return $this->estado;
    }

    public function orden(): int
    {
        return $this->orden;
    }

    public function archivoPath(): ?string
    {
        return $this->archivoPath;
    }

    public function notaRechazo(): ?string
    {
        return $this->notaRechazo;
    }

    public function maxArchivos(): int
    {
        return $this->maxArchivos;
    }

    public function withNombre(string $nombre): self
    {
        return new self(
            $this->id,
            $this->requerimientoId,
            trim($nombre),
            $this->descripcion,
            $this->responsable,
            $this->obligatorio,
            $this->estado,
            $this->orden,
            $this->archivoPath,
            $this->notaRechazo,
            $this->maxArchivos,
        );
    }

    public function withResponsable(ResponsableRequerimientoDocumento $responsable): self
    {
        if ($this->responsable === $responsable) {
            return $this;
        }

        $estado = $this->estado;
        $archivoPath = $this->archivoPath;
        $notaRechazo = $this->notaRechazo;

        if (
            EstadoRequerimientoDocumento::Entregado === $this->estado
            || EstadoRequerimientoDocumento::Validado === $this->estado
        ) {
            $estado = EstadoRequerimientoDocumento::Pendiente;
            $archivoPath = null;
            $notaRechazo = null;
        }

        return new self(
            $this->id,
            $this->requerimientoId,
            $this->nombre,
            $this->descripcion,
            $responsable,
            $this->obligatorio,
            $estado,
            $this->orden,
            $archivoPath,
            $notaRechazo,
            $this->maxArchivos,
        );
    }

    public function withArchivo(string $path): self
    {
        if ('' === trim($path)) {
            throw new \InvalidArgumentException('La ruta del archivo no puede estar vacía.');
        }

        return new self(
            $this->id,
            $this->requerimientoId,
            $this->nombre,
            $this->descripcion,
            $this->responsable,
            $this->obligatorio,
            EstadoRequerimientoDocumento::Entregado,
            $this->orden,
            $path,
            null,
            $this->maxArchivos,
        );
    }

    public function validar(): self
    {
        if (null === $this->archivoPath || '' === trim($this->archivoPath)) {
            throw new \InvalidArgumentException('No hay archivo que validar.');
        }
        if (EstadoRequerimientoDocumento::Entregado !== $this->estado) {
            throw new \InvalidArgumentException('Solo se pueden validar documentos entregados.');
        }

        return new self(
            $this->id,
            $this->requerimientoId,
            $this->nombre,
            $this->descripcion,
            $this->responsable,
            $this->obligatorio,
            EstadoRequerimientoDocumento::Validado,
            $this->orden,
            $this->archivoPath,
            null,
            $this->maxArchivos,
        );
    }

    public function rechazar(string $nota): self
    {
        $nota = trim($nota);
        if ('' === $nota) {
            throw new \InvalidArgumentException('Indique el motivo de la devolución.');
        }

        return new self(
            $this->id,
            $this->requerimientoId,
            $this->nombre,
            $this->descripcion,
            $this->responsable,
            $this->obligatorio,
            EstadoRequerimientoDocumento::Rechazado,
            $this->orden,
            $this->archivoPath,
            $nota,
            $this->maxArchivos,
        );
    }

    public function estaCompletoParaPresentacion(): bool
    {
        if (!$this->obligatorio) {
            return EstadoRequerimientoDocumento::Validado === $this->estado
                || EstadoRequerimientoDocumento::Pendiente === $this->estado;
        }

        return EstadoRequerimientoDocumento::Validado === $this->estado;
    }
}

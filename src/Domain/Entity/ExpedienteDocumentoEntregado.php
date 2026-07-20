<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;

final readonly class ExpedienteDocumentoEntregado
{
    public function __construct(
        private string $id,
        private ExpedienteId $expedienteId,
        private ?TramiteDocumentoRequeridoId $documentoRequeridoId,
        private ?ExpedienteDocumentoRequeridoId $expedienteDocumentoRequeridoId,
        private string $archivoPath,
        private EstadoDocumentoEntregado $estado,
        private \DateTimeImmutable $entregadoAt,
        private ?string $notaRechazo = null,
        private SubidoPorDocumento $subidoPor = SubidoPorDocumento::Cliente,
        private ActorResponsableDocumento $responsableActual = ActorResponsableDocumento::Cliente,
    ) {
        if (null === $documentoRequeridoId && null === $expedienteDocumentoRequeridoId) {
            throw new \InvalidArgumentException('Debe indicarse el documento requerido de trámite o de expediente.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function documentoRequeridoId(): ?TramiteDocumentoRequeridoId
    {
        return $this->documentoRequeridoId;
    }

    public function expedienteDocumentoRequeridoId(): ?ExpedienteDocumentoRequeridoId
    {
        return $this->expedienteDocumentoRequeridoId;
    }

    public function archivoPath(): string
    {
        return $this->archivoPath;
    }

    public function estado(): EstadoDocumentoEntregado
    {
        return $this->estado;
    }

    public function entregadoAt(): \DateTimeImmutable
    {
        return $this->entregadoAt;
    }

    public function notaRechazo(): ?string
    {
        return $this->notaRechazo;
    }

    public function subidoPor(): SubidoPorDocumento
    {
        return $this->subidoPor;
    }

    public function responsableActual(): ActorResponsableDocumento
    {
        return $this->responsableActual;
    }

    public function marcarEntregado(string $archivoPath): self
    {
        return $this->rebuild(
            archivoPath: $archivoPath,
            estado: EstadoDocumentoEntregado::Entregado,
            entregadoAt: new \DateTimeImmutable('now'),
            notaRechazo: null,
            subidoPor: SubidoPorDocumento::Cliente,
            responsableActual: ActorResponsableDocumento::Abogado,
        );
    }

    public function marcarValidadoPorAbogado(string $archivoPath): self
    {
        return $this->rebuild(
            archivoPath: $archivoPath,
            estado: EstadoDocumentoEntregado::Validado,
            entregadoAt: new \DateTimeImmutable('now'),
            notaRechazo: null,
            subidoPor: SubidoPorDocumento::Abogado,
            responsableActual: ActorResponsableDocumento::Abogado,
        );
    }

    public function marcarPendienteConArchivos(string $archivoPath, SubidoPorDocumento $subidoPor): self
    {
        return $this->rebuild(
            archivoPath: $archivoPath,
            estado: EstadoDocumentoEntregado::Pendiente,
            entregadoAt: new \DateTimeImmutable('now'),
            notaRechazo: null,
            subidoPor: $subidoPor,
            responsableActual: ActorResponsableDocumento::Abogado,
        );
    }

    public function marcarValidado(): self
    {
        return $this->rebuild(
            estado: EstadoDocumentoEntregado::Validado,
            notaRechazo: null,
            responsableActual: ActorResponsableDocumento::Abogado,
        );
    }

    public function marcarRechazado(string $nota): self
    {
        $nota = trim($nota);
        if ('' === $nota) {
            throw new \InvalidArgumentException('La nota de rechazo es obligatoria.');
        }

        return $this->rebuild(
            estado: EstadoDocumentoEntregado::Rechazado,
            notaRechazo: $nota,
            responsableActual: ActorResponsableDocumento::Cliente,
        );
    }

    public function asignarAlAbogado(): self
    {
        if (!in_array($this->estado, [
            EstadoDocumentoEntregado::Pendiente,
            EstadoDocumentoEntregado::Rechazado,
        ], true)) {
            throw new \InvalidArgumentException('Solo puede tomar requisitos pendientes o devueltos.');
        }

        return $this->rebuild(responsableActual: ActorResponsableDocumento::Abogado);
    }

    public function derivarAlCliente(): self
    {
        if (EstadoDocumentoEntregado::Validado === $this->estado) {
            if (SubidoPorDocumento::Abogado !== $this->subidoPor) {
                throw new \InvalidArgumentException('Este documento validado no puede derivarse al cliente.');
            }

            return $this->rebuild(
                estado: EstadoDocumentoEntregado::Pendiente,
                notaRechazo: null,
                responsableActual: ActorResponsableDocumento::Cliente,
            );
        }

        if (EstadoDocumentoEntregado::Entregado === $this->estado) {
            throw new \InvalidArgumentException('Hay un documento pendiente de revisión.');
        }

        return $this->rebuild(responsableActual: ActorResponsableDocumento::Cliente);
    }

    private function rebuild(
        ?string $archivoPath = null,
        ?EstadoDocumentoEntregado $estado = null,
        ?\DateTimeImmutable $entregadoAt = null,
        ?string $notaRechazo = null,
        ?SubidoPorDocumento $subidoPor = null,
        ?ActorResponsableDocumento $responsableActual = null,
        bool $clearNotaRechazo = false,
    ): self {
        return new self(
            $this->id,
            $this->expedienteId,
            $this->documentoRequeridoId,
            $this->expedienteDocumentoRequeridoId,
            $archivoPath ?? $this->archivoPath,
            $estado ?? $this->estado,
            $entregadoAt ?? $this->entregadoAt,
            $clearNotaRechazo ? null : ($notaRechazo ?? $this->notaRechazo),
            $subidoPor ?? $this->subidoPor,
            $responsableActual ?? $this->responsableActual,
        );
    }
}

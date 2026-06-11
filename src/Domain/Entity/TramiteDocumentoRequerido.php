<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Domain\ValueObject\TramiteId;

final readonly class TramiteDocumentoRequerido
{
    /**
     * @param list<string> $formatos
     */
    public function __construct(
        private TramiteDocumentoRequeridoId $id,
        private TramiteId $tramiteId,
        private FaseDocumentoTramite $fase,
        private string $nombre,
        private string $descripcion,
        private bool $obligatorio,
        private TipoDocumentoRequerido $tipo,
        private int $maxImagenes,
        private array $formatos,
        private int $orden,
    ) {
    }

    public function id(): TramiteDocumentoRequeridoId
    {
        return $this->id;
    }

    public function tramiteId(): TramiteId
    {
        return $this->tramiteId;
    }

    public function fase(): FaseDocumentoTramite
    {
        return $this->fase;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function obligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function tipo(): TipoDocumentoRequerido
    {
        return $this->tipo;
    }

    public function maxImagenes(): int
    {
        return $this->maxImagenes;
    }

    /**
     * @return list<string>
     */
    public function formatos(): array
    {
        return $this->formatos;
    }

    public function orden(): int
    {
        return $this->orden;
    }
}

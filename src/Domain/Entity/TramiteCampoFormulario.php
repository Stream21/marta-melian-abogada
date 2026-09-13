<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\TramiteCampoFormularioId;
use App\Domain\ValueObject\TramiteId;

final readonly class TramiteCampoFormulario
{
    /**
     * @param list<string>|null $opcionesJson
     */
    public function __construct(
        private TramiteCampoFormularioId $id,
        private TramiteId $tramiteId,
        private string $clave,
        private string $etiqueta,
        private TipoCampoFormulario $tipo,
        private bool $obligatorio,
        private int $orden,
        private ?array $opcionesJson = null,
    ) {
    }

    public function id(): TramiteCampoFormularioId
    {
        return $this->id;
    }

    public function tramiteId(): TramiteId
    {
        return $this->tramiteId;
    }

    public function clave(): string
    {
        return $this->clave;
    }

    public function etiqueta(): string
    {
        return $this->etiqueta;
    }

    public function tipo(): TipoCampoFormulario
    {
        return $this->tipo;
    }

    public function obligatorio(): bool
    {
        return $this->obligatorio;
    }

    public function orden(): int
    {
        return $this->orden;
    }

    /**
     * @return list<string>|null
     */
    public function opcionesJson(): ?array
    {
        return $this->opcionesJson;
    }
}

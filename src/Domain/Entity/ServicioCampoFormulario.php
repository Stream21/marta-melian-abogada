<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ServicioCampoFormularioId;
use App\Domain\ValueObject\ServicioId;

final readonly class ServicioCampoFormulario
{
    /**
     * @param list<string>|null $opcionesJson
     */
    public function __construct(
        private ServicioCampoFormularioId $id,
        private ServicioId $servicioId,
        private string $clave,
        private string $etiqueta,
        private TipoCampoFormulario $tipo,
        private bool $obligatorio,
        private int $orden,
        private ?array $opcionesJson = null,
    ) {
    }

    public function id(): ServicioCampoFormularioId
    {
        return $this->id;
    }

    public function servicioId(): ServicioId
    {
        return $this->servicioId;
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

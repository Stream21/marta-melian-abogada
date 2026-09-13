<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteRequerimientoCampoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final readonly class ExpedienteRequerimientoCampo
{
    /**
     * @param list<string>|null $opcionesJson
     */
    public function __construct(
        private ExpedienteRequerimientoCampoId $id,
        private ExpedienteRequerimientoMercurioId $requerimientoId,
        private string $clave,
        private string $etiqueta,
        private TipoCampoFormulario $tipo,
        private bool $obligatorio,
        private int $orden,
        private ?array $opcionesJson = null,
        private ?string $valor = null,
    ) {
        if ('' === trim($clave)) {
            throw new \InvalidArgumentException('La clave del campo es obligatoria.');
        }
        if ('' === trim($etiqueta)) {
            throw new \InvalidArgumentException('La etiqueta del campo es obligatoria.');
        }
    }

    /**
     * @param list<string>|null $opciones
     */
    public static function crear(
        ExpedienteRequerimientoCampoId $id,
        ExpedienteRequerimientoMercurioId $requerimientoId,
        string $clave,
        string $etiqueta,
        TipoCampoFormulario $tipo,
        bool $obligatorio,
        int $orden,
        ?array $opciones = null,
    ): self {
        return new self(
            $id,
            $requerimientoId,
            trim($clave),
            trim($etiqueta),
            $tipo,
            $obligatorio,
            $orden,
            $opciones,
        );
    }

    public function id(): ExpedienteRequerimientoCampoId
    {
        return $this->id;
    }

    public function requerimientoId(): ExpedienteRequerimientoMercurioId
    {
        return $this->requerimientoId;
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

    public function valor(): ?string
    {
        return $this->valor;
    }

    public function withValor(?string $valor): self
    {
        return new self(
            $this->id,
            $this->requerimientoId,
            $this->clave,
            $this->etiqueta,
            $this->tipo,
            $this->obligatorio,
            $this->orden,
            $this->opcionesJson,
            null !== $valor ? trim($valor) : null,
        );
    }

    /**
     * @param list<string>|null $opciones
     */
    public function withDefinicion(
        string $clave,
        string $etiqueta,
        TipoCampoFormulario $tipo,
        bool $obligatorio,
        int $orden,
        ?array $opciones,
    ): self {
        return new self(
            $this->id,
            $this->requerimientoId,
            trim($clave),
            trim($etiqueta),
            $tipo,
            $obligatorio,
            $orden,
            $opciones,
            $this->valor,
        );
    }

    public function estaCompleto(): bool
    {
        if (!$this->obligatorio) {
            return true;
        }
        $v = $this->valor;
        if (null === $v) {
            return false;
        }
        if (TipoCampoFormulario::Checkbox === $this->tipo) {
            return in_array(strtolower($v), ['1', 'true', 'si', 'sí', 'yes'], true);
        }

        return '' !== trim($v);
    }
}

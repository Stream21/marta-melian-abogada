<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\GastoId;

final readonly class Gasto
{
    private string $concepto;
    private string $importe;
    private ?string $categoria;
    private ?string $notas;

    public function __construct(
        private GastoId $id,
        string $concepto,
        string $importe,
        private \DateTimeImmutable $fecha,
        ?string $categoria,
        ?string $notas,
        private ?string $facturaPdfPath,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
        $concepto = trim($concepto);
        if ('' === $concepto) {
            throw new \InvalidArgumentException('El concepto del gasto es obligatorio.');
        }
        if (mb_strlen($concepto) > 255) {
            throw new \InvalidArgumentException('El concepto no puede superar 255 caracteres.');
        }

        if (!is_numeric($importe) || (float) $importe <= 0) {
            throw new \InvalidArgumentException('El importe debe ser un número mayor que cero.');
        }

        if (null !== $categoria) {
            $categoria = trim($categoria);
            if ('' === $categoria) {
                $categoria = null;
            } elseif (mb_strlen($categoria) > 100) {
                throw new \InvalidArgumentException('La categoría no puede superar 100 caracteres.');
            }
        }

        if (null !== $notas) {
            $notas = trim($notas);
            if ('' === $notas) {
                $notas = null;
            } elseif (mb_strlen($notas) > 5000) {
                throw new \InvalidArgumentException('Las notas no pueden superar 5000 caracteres.');
            }
        }

        $this->concepto = $concepto;
        $this->importe = number_format((float) $importe, 2, '.', '');
        $this->categoria = $categoria;
        $this->notas = $notas;
    }

    public function id(): GastoId
    {
        return $this->id;
    }

    public function concepto(): string
    {
        return $this->concepto;
    }

    public function importe(): string
    {
        return $this->importe;
    }

    public function fecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function categoria(): ?string
    {
        return $this->categoria;
    }

    public function notas(): ?string
    {
        return $this->notas;
    }

    public function facturaPdfPath(): ?string
    {
        return $this->facturaPdfPath;
    }

    public function tieneFactura(): bool
    {
        return null !== $this->facturaPdfPath && '' !== $this->facturaPdfPath;
    }

    public function facturaUrl(): ?string
    {
        if (!$this->tieneFactura()) {
            return null;
        }

        return '/api/gastos/' . $this->id->value() . '/factura';
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withDatos(
        string $concepto,
        string $importe,
        \DateTimeImmutable $fecha,
        ?string $categoria,
        ?string $notas,
        \DateTimeImmutable $updatedAt = new \DateTimeImmutable('now'),
    ): self {
        return new self(
            $this->id,
            $concepto,
            $importe,
            $fecha,
            $categoria,
            $notas,
            $this->facturaPdfPath,
            $this->createdAt,
            $updatedAt,
        );
    }

    public function withFacturaPdfPath(?string $facturaPdfPath, \DateTimeImmutable $updatedAt = new \DateTimeImmutable('now')): self
    {
        return new self(
            $this->id,
            $this->concepto,
            $this->importe,
            $this->fecha,
            $this->categoria,
            $this->notas,
            $facturaPdfPath,
            $this->createdAt,
            $updatedAt,
        );
    }
}

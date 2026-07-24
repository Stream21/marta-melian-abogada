<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum EstadoExpediente: string
{
    case Abierto = 'abierto';
    case Cancelado = 'cancelado';
    case Archivado = 'archivado';

    public function label(): string
    {
        return match ($this) {
            self::Abierto => 'Abierto',
            self::Cancelado => 'Cancelado',
            self::Archivado => 'Archivado',
        };
    }

    public function isOperativo(): bool
    {
        return self::Abierto === $this;
    }

    /**
     * Normaliza valores legacy de BD (cerrado/finalizado → archivado).
     */
    public static function fromStorage(string $value): self
    {
        return match ($value) {
            'cerrado', 'finalizado' => self::Archivado,
            default => self::from($value),
        };
    }
}

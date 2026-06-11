<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum FaseDocumentoTramite: int
{
    case DocumentacionBasica = 1;
    case DocumentosCliente = 2;
    case GestionAbogado = 3;
    case Resolucion = 4;

    public function label(): string
    {
        return match ($this) {
            self::DocumentacionBasica => 'Documentación básica',
            self::DocumentosCliente => 'Documentos del cliente',
            self::GestionAbogado => 'Gestión del abogado',
            self::Resolucion => 'Resolución',
        };
    }

    public static function fromValue(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_string($value)) {
            return match ($value) {
                'apertura' => self::DocumentosCliente,
                'resolucion' => self::Resolucion,
                default => self::fromInt((int) $value),
            };
        }

        return self::fromInt((int) $value);
    }

    public static function fromInt(int $value): self
    {
        return match ($value) {
            1 => self::DocumentacionBasica,
            2 => self::DocumentosCliente,
            3 => self::GestionAbogado,
            4 => self::Resolucion,
            default => throw new \InvalidArgumentException('Fase de documento no válida. Debe ser un valor entre 1 y 4.'),
        };
    }
}

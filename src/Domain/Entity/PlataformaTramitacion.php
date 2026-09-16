<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum PlataformaTramitacion: string
{
    case Mercurio = 'mercurio';
    case LexNet = 'lexnet';
    case MinisterioJusticia = 'ministerio_justicia';
    case RegistroCivil = 'registro_civil';
    case Notaria = 'notaria';
    case RegistroPropiedad = 'registro_propiedad';

    public function label(): string
    {
        return match ($this) {
            self::Mercurio => 'Mercurio',
            self::LexNet => 'LexNET',
            self::MinisterioJusticia => 'Ministerio de Justicia',
            self::RegistroCivil => 'Registro Civil',
            self::Notaria => 'Notaría',
            self::RegistroPropiedad => 'Registro de la Propiedad',
        };
    }

    public static function fromString(string $value): self
    {
        $plataforma = self::tryFrom(trim($value));
        if (null === $plataforma) {
            throw new \InvalidArgumentException(
                'Plataforma de tramitación no válida. Valores permitidos: '
                . implode(', ', array_map(static fn (self $p) => $p->value, self::cases())),
            );
        }

        return $plataforma;
    }
}

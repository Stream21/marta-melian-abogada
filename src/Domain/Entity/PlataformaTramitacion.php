<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum PlataformaTramitacion: string
{
    case Mercurio = 'mercurio';
    case LexNet = 'lexnet';

    public function label(): string
    {
        return match ($this) {
            self::Mercurio => 'Mercurio',
            self::LexNet => 'LexNET',
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

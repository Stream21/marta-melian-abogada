<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TipoCampoFormulario: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case Checkbox = 'checkbox';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto',
            self::Textarea => 'Texto largo',
            self::Number => 'Número',
            self::Date => 'Fecha',
            self::Select => 'Selección',
            self::Checkbox => 'Casilla',
        };
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new \InvalidArgumentException('Tipo de campo de formulario no válido.');
    }
}

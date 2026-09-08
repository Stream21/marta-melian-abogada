<?php

declare(strict_types=1);

namespace App\Infrastructure\Document\Mrz;

/**
 * Lectura de una banda MRZ ya validada y normalizada.
 */
final class MrzResult
{
    /**
     * @param list<string> $lineas
     * @param list<string> $checksValidos  Campos cuyo dígito de control cuadra
     * @param list<string> $checksFallidos Campos cuyo dígito de control no cuadra
     */
    public function __construct(
        public readonly string $formato,
        public readonly string $codigoDocumento,
        public readonly ?string $paisEmisor,
        public readonly string $numDocumento,
        public readonly ?string $nacionalidad,
        public readonly ?string $fechaNacimiento,
        public readonly ?string $fechaCaducidad,
        public readonly string $sexo,
        public readonly string $apellidos,
        public readonly string $nombrePila,
        public readonly int $confianza,
        public readonly array $checksValidos = [],
        public readonly array $checksFallidos = [],
        public readonly array $lineas = [],
    ) {
    }

    public function nombreCompleto(): string
    {
        return trim(preg_replace('/\s+/', ' ', $this->nombrePila . ' ' . $this->apellidos) ?? '');
    }

    public function checkValido(string $campo): bool
    {
        return in_array($campo, $this->checksValidos, true);
    }

    public function tieneDatos(): bool
    {
        return '' !== $this->numDocumento || '' !== $this->apellidos;
    }
}

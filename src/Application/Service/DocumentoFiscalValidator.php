<?php

declare(strict_types=1);

namespace App\Application\Service;

/**
 * Valida documento identificativo del cliente antes de sync Holded.
 * DNI/NIE: formato + dígito de control. Pasaporte: alfanumérico.
 * País: ISO-2.
 */
final class DocumentoFiscalValidator
{
    private const LETRAS = 'TRWAGMYFPDXBNJZSQVHLCKE';
    private const PREFIJOS_NIE = ['X' => '0', 'Y' => '1', 'Z' => '2'];

    public function assertValid(string $tipoDocumento, string $numDocumento, string $countryCode = 'ES'): void
    {
        $tipo = strtoupper(trim($tipoDocumento));
        $numero = strtoupper(preg_replace('/[\s\-]/', '', $numDocumento) ?? '');
        $pais = strtoupper(trim($countryCode));

        if ('' === $pais || 1 !== preg_match('/^[A-Z]{2}$/', $pais)) {
            throw new \InvalidArgumentException('El código de país debe ser ISO de 2 letras (ej. ES).');
        }

        if ('' === $numero) {
            throw new \InvalidArgumentException('El número de documento es obligatorio para facturar en Holded.');
        }

        if (in_array($tipo, ['DNI', 'NIF'], true)) {
            if (!$this->esDni($numero) && !$this->tieneFormatoDniNie($numero)) {
                throw new \InvalidArgumentException('El DNI/NIF no tiene un formato válido.');
            }

            return;
        }

        if ('NIE' === $tipo) {
            if (!$this->esNie($numero) && !$this->tieneFormatoDniNie($numero)) {
                throw new \InvalidArgumentException('El NIE no tiene un formato válido.');
            }

            return;
        }

        if (in_array($tipo, ['PASAPORTE', 'PASSPORT', '', 'OTRO'], true)) {
            if (1 !== preg_match('/^[A-Z0-9]{5,20}$/', $numero)) {
                throw new \InvalidArgumentException('El pasaporte/documento debe ser alfanumérico (5–20 caracteres).');
            }

            return;
        }

        if (1 !== preg_match('/^[A-Z0-9]{5,20}$/', $numero)) {
            throw new \InvalidArgumentException('El número de documento no tiene un formato válido.');
        }
    }

    private function esDni(string $valor): bool
    {
        if (!preg_match('/^(\d{8})([A-Z])$/', $valor, $m)) {
            return false;
        }

        return self::LETRAS[((int) $m[1]) % 23] === $m[2];
    }

    private function esNie(string $valor): bool
    {
        if (!preg_match('/^([XYZ])(\d{7})([A-Z])$/', $valor, $m)) {
            return false;
        }

        $numero = (int) (self::PREFIJOS_NIE[$m[1]] . $m[2]);

        return self::LETRAS[$numero % 23] === $m[3];
    }

    private function tieneFormatoDniNie(string $valor): bool
    {
        return 1 === preg_match('/^\d{8}[A-Z]$/', $valor)
            || 1 === preg_match('/^[XYZ]\d{7}[A-Z]$/', $valor);
    }
}

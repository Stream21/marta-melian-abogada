<?php

declare(strict_types=1);

namespace App\Application\Service;

/**
 * Convierte importes en euros a texto en mayúsculas (uso en contratos).
 * Ej.: 500.0 → "QUINIENTOS EUROS", 1250.50 → "MIL DOSCIENTOS CINCUENTA EUROS CON CINCUENTA CÉNTIMOS".
 */
final class EurosEnLetrasConverter
{
    private const UNIDADES = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
    ];

    private const DIEZ_A_DIECINUEVE = [
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
    ];

    private const DECENAS = [
        '', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA',
    ];

    private const CENTENAS = [
        '', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
    ];

    public function convertir(float $importe): string
    {
        if ($importe < 0) {
            throw new \InvalidArgumentException('El importe no puede ser negativo.');
        }

        $entero = (int) round($importe * 100) / 100;
        $parteEntera = (int) floor($entero);
        $centimos = (int) round(($entero - $parteEntera) * 100);

        if (0 === $parteEntera && 0 === $centimos) {
            return 'CERO EUROS';
        }

        $texto = $this->enteroEnLetras($parteEntera);
        if (1 === $parteEntera) {
            $texto = 'UN';
        }

        $resultado = 1 === $parteEntera ? 'UN EURO' : $texto . ' EUROS';

        if ($centimos > 0) {
            $centimosTexto = $this->enteroEnLetras($centimos);
            if (1 === $centimos) {
                $centimosTexto = 'UN';
            }
            $resultado .= ' CON ' . $centimosTexto . (1 === $centimos ? ' CÉNTIMO' : ' CÉNTIMOS');
        }

        return $resultado;
    }

    private function enteroEnLetras(int $n): string
    {
        if (0 === $n) {
            return 'CERO';
        }

        if ($n < 10) {
            return self::UNIDADES[$n];
        }

        if ($n < 20) {
            return self::DIEZ_A_DIECINUEVE[$n - 10];
        }

        if ($n < 30) {
            $veinti = [
                20 => 'VEINTE',
                21 => 'VEINTIUNO',
                22 => 'VEINTIDÓS',
                23 => 'VEINTITRÉS',
                24 => 'VEINTICUATRO',
                25 => 'VEINTICINCO',
                26 => 'VEINTISÉIS',
                27 => 'VEINTISIETE',
                28 => 'VEINTIOCHO',
                29 => 'VEINTINUEVE',
            ];

            return $veinti[$n] ?? self::DECENAS[intdiv($n, 10)];
        }

        if ($n < 100) {
            $decena = intdiv($n, 10);
            $unidad = $n % 10;

            return self::DECENAS[$decena] . ($unidad > 0 ? ' Y ' . self::UNIDADES[$unidad] : '');
        }

        if (100 === $n) {
            return 'CIEN';
        }

        if ($n < 1000) {
            $centena = intdiv($n, 100);
            $resto = $n % 100;

            return self::CENTENAS[$centena] . ($resto > 0 ? ' ' . $this->enteroEnLetras($resto) : '');
        }

        if ($n < 2000) {
            $resto = $n % 1000;

            return 'MIL' . ($resto > 0 ? ' ' . $this->enteroEnLetras($resto) : '');
        }

        if ($n < 1000000) {
            $miles = intdiv($n, 1000);
            $resto = $n % 1000;
            $milesTexto = (1 === $miles ? 'MIL' : $this->enteroEnLetras($miles) . ' MIL');

            return $milesTexto . ($resto > 0 ? ' ' . $this->enteroEnLetras($resto) : '');
        }

        if ($n < 2000000) {
            $resto = $n % 1000000;

            return 'UN MILLÓN' . ($resto > 0 ? ' ' . $this->enteroEnLetras($resto) : '');
        }

        if ($n < 1000000000) {
            $millones = intdiv($n, 1000000);
            $resto = $n % 1000000;
            $millonesTexto = $this->enteroEnLetras($millones) . ' MILLONES';

            return $millonesTexto . ($resto > 0 ? ' ' . $this->enteroEnLetras($resto) : '');
        }

        throw new \InvalidArgumentException('Importe demasiado grande para convertir a letras.');
    }
}

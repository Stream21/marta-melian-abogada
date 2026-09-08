<?php

declare(strict_types=1);

namespace App\Infrastructure\Document\Mrz;

/**
 * Lector de bandas MRZ ICAO 9303: TD1 (3×30, DNI/NIE), TD2 (2×36) y TD3 (2×44, pasaporte).
 *
 * Sobre un OCR ruidoso se generan varias alineaciones de cada línea, se corrigen los
 * glifos según el tipo esperado de cada campo y se puntúa cada hipótesis con los
 * dígitos de control. Se devuelve la lectura de mayor confianza.
 */
final class MrzReader
{
    private const LONGITUD_TD1 = 30;
    private const LONGITUD_TD2 = 36;
    private const LONGITUD_TD3 = 44;

    private const MAX_ALINEACIONES = 3;

    public function read(string $texto): ?MrzResult
    {
        $lineas = $this->lineasCandidatas($texto);
        if (count($lineas) < 2) {
            return null;
        }

        $mejor = null;

        foreach ($this->hipotesis($lineas) as $resultado) {
            if (null === $mejor || $resultado->confianza > $mejor->confianza) {
                $mejor = $resultado;
            }
        }

        return null !== $mejor && $mejor->tieneDatos() ? $mejor : null;
    }

    /**
     * @param list<string> $lineas
     *
     * @return list<MrzResult>
     */
    private function hipotesis(array $lineas): array
    {
        $resultados = [];
        $total = count($lineas);

        for ($i = 0; $i < $total; ++$i) {
            $l1 = $lineas[$i];

            if ($i + 1 < $total) {
                $l2 = $lineas[$i + 1];

                if ($this->encaja($l1, self::LONGITUD_TD3) && $this->encaja($l2, self::LONGITUD_TD3)) {
                    foreach ($this->alineaciones($l1, self::LONGITUD_TD3) as $a) {
                        foreach ($this->alineaciones($l2, self::LONGITUD_TD3) as $b) {
                            $r = $this->parseTd3($a, $b);
                            if (null !== $r) {
                                $resultados[] = $r;
                            }
                        }
                    }
                }

                if ($this->encaja($l1, self::LONGITUD_TD2) && $this->encaja($l2, self::LONGITUD_TD2)) {
                    foreach ($this->alineaciones($l1, self::LONGITUD_TD2) as $a) {
                        foreach ($this->alineaciones($l2, self::LONGITUD_TD2) as $b) {
                            $r = $this->parseTd2($a, $b);
                            if (null !== $r) {
                                $resultados[] = $r;
                            }
                        }
                    }
                }
            }

            if ($i + 2 < $total) {
                $l2 = $lineas[$i + 1];
                $l3 = $lineas[$i + 2];

                if (
                    $this->encaja($l1, self::LONGITUD_TD1)
                    && $this->encaja($l2, self::LONGITUD_TD1)
                    && $this->encaja($l3, self::LONGITUD_TD1)
                ) {
                    foreach ($this->alineaciones($l1, self::LONGITUD_TD1) as $a) {
                        foreach ($this->alineaciones($l2, self::LONGITUD_TD1) as $b) {
                            foreach ($this->alineaciones($l3, self::LONGITUD_TD1) as $c) {
                                $r = $this->parseTd1($a, $b, $c);
                                if (null !== $r) {
                                    $resultados[] = $r;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $resultados;
    }

    // ---------------------------------------------------------------- TD1

    private function parseTd1(string $l1, string $l2, string $l3): ?MrzResult
    {
        $codigo = rtrim(MrzOcrCorrector::aLetras(substr($l1, 0, 2)), '<');
        if ('' === $codigo || 1 !== preg_match('/^[ACI]/', $codigo)) {
            return null;
        }

        $pais = MrzOcrCorrector::aLetras(substr($l1, 2, 3));
        $numCampo = strtoupper(substr($l1, 5, 9));
        $numCheck = MrzOcrCorrector::aDigitos(substr($l1, 14, 1));
        $opcional1 = substr($l1, 15, 15);

        $l1Corr = substr($codigo . '<<', 0, 2) . $pais . $numCampo . $numCheck . $opcional1;

        $fNac = MrzOcrCorrector::aDigitos(substr($l2, 0, 6));
        $fNacCheck = MrzOcrCorrector::aDigitos(substr($l2, 6, 1));
        $sexo = $this->sexo(substr($l2, 7, 1));
        $fCad = MrzOcrCorrector::aDigitos(substr($l2, 8, 6));
        $fCadCheck = MrzOcrCorrector::aDigitos(substr($l2, 14, 1));
        $nacionalidad = MrzOcrCorrector::aLetras(substr($l2, 15, 3));
        $opcional2 = substr($l2, 18, 11);
        $compCheck = MrzOcrCorrector::aDigitos(substr($l2, 29, 1));

        $l2Corr = $fNac . $fNacCheck . ('' !== $sexo ? $sexo : '<') . $fCad . $fCadCheck
            . $nacionalidad . $opcional2 . $compCheck;

        $compuesto = substr($l1Corr, 5, 25) . substr($l2Corr, 0, 7) . substr($l2Corr, 8, 7) . substr($l2Corr, 18, 11);

        $validos = [];
        $fallidos = [];
        $this->registrarCheck('numDocumento', MrzCheckDigit::matches($numCampo, $numCheck), $validos, $fallidos);
        $this->registrarCheck('fechaNacimiento', MrzCheckDigit::matches($fNac, $fNacCheck), $validos, $fallidos);
        $this->registrarCheck('fechaCaducidad', MrzCheckDigit::matches($fCad, $fCadCheck), $validos, $fallidos);
        $this->registrarCheck('compuesto', MrzCheckDigit::matches($compuesto, $compCheck), $validos, $fallidos);

        $nombres = $this->nombres($l3);
        $numDocumento = $this->numeroTd1($numCampo, $opcional1, $pais);

        return $this->construir(
            'TD1',
            $codigo,
            $pais,
            $numDocumento,
            $nacionalidad,
            $fNac,
            $fCad,
            $sexo,
            $nombres,
            $validos,
            $fallidos,
            [$l1Corr, $l2Corr, $l3],
        );
    }

    /**
     * En el DNI/NIE español el campo de número contiene el número de soporte (BAA000589)
     * y el DNI/NIE viaja en los datos opcionales de la línea 1.
     */
    private function numeroTd1(string $campo, string $opcional, string $pais): string
    {
        $dniNie = $this->buscarDniNie($opcional) ?? $this->buscarDniNie($campo);
        if (null !== $dniNie) {
            return $dniNie;
        }

        $limpio = trim(str_replace('<', '', $campo));
        if ('' === $limpio) {
            return '';
        }

        // Sin DNI/NIE legible, el número de soporte no identifica al cliente: mejor vacío.
        if ('ESP' === MrzCountryResolver::resolverCodigo($pais) && $this->esNumeroSoporte($limpio)) {
            return '';
        }

        return $limpio;
    }

    // ---------------------------------------------------------------- TD2 / TD3

    private function parseTd3(string $l1, string $l2): ?MrzResult
    {
        if ('P' !== MrzOcrCorrector::aLetras(substr($l1, 0, 1))) {
            return null;
        }

        return $this->parseDosLineas('TD3', $l1, $l2, self::LONGITUD_TD3, 28, 14);
    }

    private function parseTd2(string $l1, string $l2): ?MrzResult
    {
        $codigo = rtrim(MrzOcrCorrector::aLetras(substr($l1, 0, 2)), '<');
        if ('' === $codigo || 1 !== preg_match('/^[ACIV]/', $codigo)) {
            return null;
        }

        return $this->parseDosLineas('TD2', $l1, $l2, self::LONGITUD_TD2, 28, 7);
    }

    /**
     * TD2 y TD3 comparten el diseño de la línea 2; solo cambian la longitud del campo
     * opcional y si este lleva dígito de control propio (TD3 sí, TD2 no).
     */
    private function parseDosLineas(
        string $formato,
        string $l1,
        string $l2,
        int $longitud,
        int $inicioOpcional,
        int $largoOpcional,
    ): ?MrzResult {
        $codigo = rtrim(MrzOcrCorrector::aLetras(substr($l1, 0, 2)), '<');
        $pais = MrzOcrCorrector::aLetras(substr($l1, 2, 3));
        $campoNombres = substr($l1, 5, $longitud - 5);

        $numCampo = strtoupper(substr($l2, 0, 9));
        $numCheck = MrzOcrCorrector::aDigitos(substr($l2, 9, 1));
        $nacionalidad = MrzOcrCorrector::aLetras(substr($l2, 10, 3));
        $fNac = MrzOcrCorrector::aDigitos(substr($l2, 13, 6));
        $fNacCheck = MrzOcrCorrector::aDigitos(substr($l2, 19, 1));
        $sexo = $this->sexo(substr($l2, 20, 1));
        $fCad = MrzOcrCorrector::aDigitos(substr($l2, 21, 6));
        $fCadCheck = MrzOcrCorrector::aDigitos(substr($l2, 27, 1));
        $opcional = substr($l2, $inicioOpcional, $largoOpcional);
        $opcionalCheck = 'TD3' === $formato ? MrzOcrCorrector::aDigitos(substr($l2, 42, 1)) : '';
        $compCheck = MrzOcrCorrector::aDigitos(substr($l2, $longitud - 1, 1));

        $l2Corr = $numCampo . $numCheck . $nacionalidad . $fNac . $fNacCheck
            . ('' !== $sexo ? $sexo : '<') . $fCad . $fCadCheck . $opcional . $opcionalCheck . $compCheck;

        // ICAO 9303: el dígito compuesto cubre número + nacimiento + caducidad + datos opcionales.
        $compuesto = substr($l2Corr, 0, 10) . substr($l2Corr, 13, 7) . substr($l2Corr, 21, $longitud - 22);

        $validos = [];
        $fallidos = [];
        $this->registrarCheck('numDocumento', MrzCheckDigit::matches($numCampo, $numCheck), $validos, $fallidos);
        $this->registrarCheck('fechaNacimiento', MrzCheckDigit::matches($fNac, $fNacCheck), $validos, $fallidos);
        $this->registrarCheck('fechaCaducidad', MrzCheckDigit::matches($fCad, $fCadCheck), $validos, $fallidos);
        $this->registrarCheck('compuesto', MrzCheckDigit::matches($compuesto, $compCheck), $validos, $fallidos);

        $numDocumento = trim(str_replace('<', '', $numCampo));

        return $this->construir(
            $formato,
            $codigo,
            $pais,
            $numDocumento,
            $nacionalidad,
            $fNac,
            $fCad,
            $sexo,
            $this->nombres($campoNombres),
            $validos,
            $fallidos,
            [$l1, $l2Corr],
        );
    }

    // ---------------------------------------------------------------- Común

    /**
     * @param array{apellidos: string, nombre: string} $nombres
     * @param list<string>                             $validos
     * @param list<string>                             $fallidos
     * @param list<string>                             $lineas
     */
    private function construir(
        string $formato,
        string $codigo,
        string $paisRaw,
        string $numDocumento,
        string $nacionalidadRaw,
        string $fNacRaw,
        string $fCadRaw,
        string $sexo,
        array $nombres,
        array $validos,
        array $fallidos,
        array $lineas,
    ): MrzResult {
        $pais = MrzCountryResolver::resolverCodigo($paisRaw);
        $nacionalidad = MrzCountryResolver::resolverCodigo($nacionalidadRaw) ?? $pais;

        $fechaNacimiento = $this->fecha($fNacRaw, false);
        $fechaCaducidad = $this->fecha($fCadRaw, true);

        $confianza = $this->puntuar(
            $validos,
            $numDocumento,
            $nombres,
            $pais,
            $nacionalidad,
            $fechaNacimiento,
            $fechaCaducidad,
        );

        return new MrzResult(
            $formato,
            $codigo,
            $pais,
            $numDocumento,
            $nacionalidad,
            $fechaNacimiento,
            $fechaCaducidad,
            $sexo,
            $nombres['apellidos'],
            $nombres['nombre'],
            $confianza,
            $validos,
            $fallidos,
            $lineas,
        );
    }

    /**
     * @param list<string>                             $validos
     * @param array{apellidos: string, nombre: string} $nombres
     */
    private function puntuar(
        array $validos,
        string $numDocumento,
        array $nombres,
        ?string $pais,
        ?string $nacionalidad,
        ?string $fechaNacimiento,
        ?string $fechaCaducidad,
    ): int {
        $puntos = 0;

        $puntos += in_array('numDocumento', $validos, true) ? 26 : 0;
        $puntos += in_array('fechaNacimiento', $validos, true) ? 18 : 0;
        $puntos += in_array('fechaCaducidad', $validos, true) ? 16 : 0;
        $puntos += in_array('compuesto', $validos, true) ? 22 : 0;

        if (NumeroDocumentoEspanol::esValido($numDocumento)) {
            $puntos += 14;
        } elseif ('' !== $numDocumento) {
            $puntos += 4;
        }

        if ('' !== $nombres['apellidos']) {
            $puntos += 6;
        }
        if ('' !== $nombres['nombre']) {
            $puntos += 4;
        }
        if (null !== $pais) {
            $puntos += 4;
        }
        if (null !== $nacionalidad) {
            $puntos += 4;
        }
        if (null !== $fechaNacimiento) {
            $puntos += 3;
        }
        if (null !== $fechaCaducidad) {
            $puntos += 3;
        }

        return min(100, $puntos);
    }

    /**
     * @param list<string> $validos
     * @param list<string> $fallidos
     */
    private function registrarCheck(string $campo, bool $ok, array &$validos, array &$fallidos): void
    {
        if ($ok) {
            $validos[] = $campo;
        } else {
            $fallidos[] = $campo;
        }
    }

    private function buscarDniNie(string $texto): ?string
    {
        $limpio = strtoupper(str_replace('<', '', $texto));

        if (preg_match('/([XYZ]\d{7}[A-Z])/', $limpio, $m)) {
            $corregido = NumeroDocumentoEspanol::corregir($m[1]);
            if (null !== $corregido) {
                return $corregido;
            }
        }
        if (preg_match('/(\d{8}[A-Z])/', $limpio, $m)) {
            $corregido = NumeroDocumentoEspanol::corregir($m[1]);
            if (null !== $corregido) {
                return $corregido;
            }
        }

        // Lectura sucia: corregir bloques de 9 caracteres que ya se parezcan a un DNI/NIE.
        // Sin ese parecido previo, la letra de control (1 de 23) validaría por azar
        // números que en realidad son el número de soporte de la tarjeta.
        $longitud = strlen($limpio);
        for ($i = 0; $i + 9 <= $longitud; ++$i) {
            $bloque = substr($limpio, $i, 9);
            if (!$this->pareceDniNie($bloque)) {
                continue;
            }
            $candidato = NumeroDocumentoEspanol::corregir($bloque);
            if (null !== $candidato && NumeroDocumentoEspanol::esValido($candidato)) {
                return $candidato;
            }
        }

        return null;
    }

    /** Al menos 6 de los 8 caracteres centrales ya son dígitos y el último no lo es. */
    private function pareceDniNie(string $bloque): bool
    {
        if (9 !== strlen($bloque)) {
            return false;
        }
        if (1 === preg_match('/\d/', substr($bloque, 8, 1))) {
            return false;
        }

        $digitos = 0;
        for ($i = 1; $i < 8; ++$i) {
            if (ctype_digit($bloque[$i])) {
                ++$digitos;
            }
        }

        return $digitos >= 6;
    }

    private function esNumeroSoporte(string $valor): bool
    {
        return 1 === preg_match('/^[A-Z]{3}\d{6,7}$/', strtoupper($valor));
    }

    private function sexo(string $valor): string
    {
        return match (strtoupper($valor)) {
            'M' => 'M',
            'F' => 'F',
            default => '',
        };
    }

    private function fecha(string $yymmdd, bool $esFutura): ?string
    {
        if (1 !== preg_match('/^\d{6}$/', $yymmdd)) {
            return null;
        }

        $yy = (int) substr($yymmdd, 0, 2);
        $mm = (int) substr($yymmdd, 2, 2);
        $dd = (int) substr($yymmdd, 4, 2);

        $anioActual = (int) date('Y');
        $anio = 2000 + $yy;

        if ($esFutura) {
            if ($anio < $anioActual - 15) {
                $anio += 100;
            }
        } elseif ($anio > $anioActual) {
            $anio -= 100;
        }

        if (!checkdate($mm, $dd, $anio)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $anio, $mm, $dd);
    }

    /**
     * @return array{apellidos: string, nombre: string}
     */
    public function nombres(string $campo): array
    {
        $limpio = trim(MrzOcrCorrector::aLetras($campo), '<');

        if (str_contains($limpio, '<<')) {
            [$apellidos, $nombre] = array_pad(explode('<<', $limpio, 2), 2, '');
        } else {
            $segmentos = array_values(array_filter(explode('<', $limpio), static fn (string $s): bool => '' !== $s));
            if (count($segmentos) >= 2) {
                $nombre = (string) array_pop($segmentos);
                $apellidos = implode(' ', $segmentos);
            } else {
                $apellidos = $limpio;
                $nombre = '';
            }
        }

        return [
            'apellidos' => $this->limpiarNombre((string) $apellidos),
            'nombre' => $this->limpiarNombre((string) $nombre),
        ];
    }

    private function limpiarNombre(string $valor): string
    {
        $v = str_replace('<', ' ', $valor);
        $v = preg_replace('/[^A-Z ]/', '', strtoupper($v)) ?? '';

        return trim(preg_replace('/\s+/', ' ', $v) ?? '');
    }

    private function encaja(string $linea, int $longitud): bool
    {
        $len = strlen($linea);

        return $len >= $longitud - 6 && $len <= $longitud + 6;
    }

    /**
     * @return list<string>
     */
    private function alineaciones(string $linea, int $longitud): array
    {
        $len = strlen($linea);

        if ($len === $longitud) {
            return [$linea];
        }

        if ($len < $longitud) {
            return [str_pad($linea, $longitud, '<')];
        }

        $variantes = [];
        $maxOffset = min($len - $longitud, self::MAX_ALINEACIONES - 1);
        for ($offset = 0; $offset <= $maxOffset; ++$offset) {
            $variantes[] = substr($linea, $offset, $longitud);
        }
        $variantes[] = substr($linea, $len - $longitud, $longitud);

        return array_values(array_unique($variantes));
    }

    /**
     * @return list<string>
     */
    public function lineasCandidatas(string $texto): array
    {
        $normalizado = MrzOcrCorrector::normalizarTexto($texto);

        $lineas = [];
        foreach (preg_split('/\R/', $normalizado) ?: [] as $bruta) {
            foreach ($this->trozosMrz($bruta) as $trozo) {
                $lineas[] = $trozo;
            }
        }

        // El OCR a veces devuelve la banda entera sin saltos de línea.
        $sinEspacios = preg_replace('/[^A-Z0-9<]/', '', $normalizado) ?? '';
        if (preg_match_all('/[A-Z0-9<]{24,}/', $sinEspacios, $m)) {
            foreach ($m[0] as $bloque) {
                if (!in_array($bloque, $lineas, true)) {
                    $lineas[] = $bloque;
                }
            }
        }

        return array_values(array_filter($lineas, static fn (string $l): bool => strlen($l) >= 18));
    }

    /**
     * Dentro de una línea de OCR puede haber espacios espurios en medio de la MRZ.
     *
     * @return list<string>
     */
    private function trozosMrz(string $linea): array
    {
        $compacta = preg_replace('/[^A-Z0-9<]/', '', $linea) ?? '';
        if (strlen($compacta) >= 18) {
            return [$compacta];
        }

        return [];
    }
}

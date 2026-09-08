<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

use App\Infrastructure\Document\Mrz\MrzCountryResolver;
use App\Infrastructure\Document\Mrz\MrzReader;
use App\Infrastructure\Document\Mrz\MrzResult;
use App\Infrastructure\Document\Mrz\NumeroDocumentoEspanol;

/**
 * Traduce una lectura MRZ (TD1 DNI/NIE, TD3 pasaporte) a los campos del formulario de cliente.
 */
final class DocumentoIdentidadMrzParser
{
    /** Por debajo de este umbral la lectura no es fiable y se descarta. */
    private const CONFIANZA_MINIMA = 20;

    public function __construct(private readonly MrzReader $reader = new MrzReader())
    {
    }

    /**
     * @return array{
     *   nombre: string,
     *   nacionalidad: string,
     *   tipoDocumento: string,
     *   numDocumento: string,
     *   fechaNacimiento: string|null,
     *   lugarNacimiento: string,
     *   extraccionAutomatica: bool
     * }|null
     */
    public function parseFromText(string $text): ?array
    {
        $mrz = $this->leer($text);

        return null === $mrz ? null : $this->mapear($mrz);
    }

    /** Lectura cruda de la MRZ, con su nivel de confianza, para comparar varios OCR. */
    public function leer(string $text): ?MrzResult
    {
        $mrz = $this->reader->read($text);

        return null !== $mrz && $mrz->confianza >= self::CONFIANZA_MINIMA ? $mrz : null;
    }

    /**
     * @return array{
     *   nombre: string,
     *   nacionalidad: string,
     *   tipoDocumento: string,
     *   numDocumento: string,
     *   fechaNacimiento: string|null,
     *   lugarNacimiento: string,
     *   extraccionAutomatica: bool
     * }|null
     */
    public function mapear(MrzResult $mrz): ?array
    {
        $tipoDocumento = $this->tipoDocumento($mrz);
        $numDocumento = $this->numDocumento($mrz, $tipoDocumento);
        $nombre = $mrz->nombreCompleto();

        if ('' === $numDocumento && '' === $nombre) {
            return null;
        }

        return [
            'nombre' => $nombre,
            'nacionalidad' => $this->nacionalidad($mrz),
            'tipoDocumento' => $tipoDocumento,
            'numDocumento' => $numDocumento,
            'fechaNacimiento' => $mrz->fechaNacimiento,
            'lugarNacimiento' => '',
            'extraccionAutomatica' => true,
        ];
    }

    /** Extrae solo el nombre completo de la MRZ (línea de titular). */
    public function parseNombreFromText(string $text): string
    {
        $mrz = $this->reader->read($text);
        if (null !== $mrz && '' !== $mrz->nombreCompleto()) {
            return $mrz->nombreCompleto();
        }

        return $this->nombreDesdeLineaSuelta($text);
    }

    /**
     * La MRZ puede quedar tan degradada que no pase el lector completo pero la línea
     * de nombres siga siendo legible (no lleva dígito de control).
     */
    private function nombreDesdeLineaSuelta(string $text): string
    {
        $candidatas = $this->reader->lineasCandidatas($text);

        foreach (array_reverse($candidatas) as $linea) {
            if (1 !== preg_match('/[A-Z]{4,}/', $linea) || substr_count($linea, '<') < 2) {
                continue;
            }
            if (1 === preg_match('/\d{5,}/', $linea)) {
                continue;
            }

            $nombres = $this->reader->nombres($linea);
            $completo = trim($nombres['nombre'] . ' ' . $nombres['apellidos']);
            if ('' !== $completo) {
                return trim(preg_replace('/\s+/', ' ', $completo) ?? '');
            }
        }

        return '';
    }

    private function tipoDocumento(MrzResult $mrz): string
    {
        if ('TD3' === $mrz->formato || str_starts_with($mrz->codigoDocumento, 'P')) {
            return 'PASAPORTE';
        }

        $num = strtoupper($mrz->numDocumento);
        if (1 === preg_match('/^[XYZ]\d{7}[A-Z]$/', $num)) {
            return 'NIE';
        }
        if (1 === preg_match('/^\d{8}[A-Z]$/', $num)) {
            return 'DNI';
        }

        return 'DNI';
    }

    private function numDocumento(MrzResult $mrz, string $tipoDocumento): string
    {
        $num = strtoupper(trim($mrz->numDocumento, "< \t\n\r"));
        if ('' === $num) {
            return '';
        }

        if ('PASAPORTE' === $tipoDocumento) {
            return preg_replace('/[^A-Z0-9]/', '', $num) ?? '';
        }

        $corregido = NumeroDocumentoEspanol::corregir($num);

        return $corregido ?? $num;
    }

    /** Devuelve el gentilicio; vacío si el código no se pudo resolver (mejor que un «ES7»). */
    private function nacionalidad(MrzResult $mrz): string
    {
        foreach ([$mrz->nacionalidad, $mrz->paisEmisor] as $codigo) {
            if (null === $codigo) {
                continue;
            }
            $nombre = MrzCountryResolver::resolverNacionalidad($codigo);
            if (null !== $nombre) {
                return $nombre;
            }
        }

        return '';
    }
}

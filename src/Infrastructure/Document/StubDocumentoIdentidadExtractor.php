<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

use App\Application\Port\DocumentoIdentidadExtractorPort;
use App\Domain\Entity\TipoEscaneoDocumentoIdentidad;

/**
 * Stub de extracción OCR. Sustituir por proveedor real (Azure, Google Vision, etc.).
 */
final class StubDocumentoIdentidadExtractor implements DocumentoIdentidadExtractorPort
{
    public function extract(string $tipoEscaneo, string $anversoPath, ?string $reversoPath): array
    {
        $tipo = TipoEscaneoDocumentoIdentidad::tryFrom($tipoEscaneo) ?? TipoEscaneoDocumentoIdentidad::DniNie;

        return $this->vacio($tipo === TipoEscaneoDocumentoIdentidad::Pasaporte ? 'PASAPORTE' : 'DNI', false);
    }

    /**
     * @return array{
     *   nombre: string,
     *   nacionalidad: string,
     *   tipoDocumento: string,
     *   numDocumento: string,
     *   fechaNacimiento: string|null,
     *   lugarNacimiento: string,
     *   domicilio: string,
     *   codigoPostal: string,
     *   ciudad: string,
     *   provincia: string,
     *   nombrePadre: string,
     *   nombreMadre: string,
     *   extraccionAutomatica: bool
     * }
     */
    public function vacio(string $tipoDocumento = 'DNI', bool $automatica = false): array
    {
        return [
            'nombre' => '',
            'nacionalidad' => '',
            'tipoDocumento' => $tipoDocumento,
            'numDocumento' => '',
            'fechaNacimiento' => null,
            'lugarNacimiento' => '',
            'domicilio' => '',
            'codigoPostal' => '',
            'ciudad' => '',
            'provincia' => '',
            'nombrePadre' => '',
            'nombreMadre' => '',
            'extraccionAutomatica' => $automatica,
            'camposMrz' => [],
        ];
    }
}

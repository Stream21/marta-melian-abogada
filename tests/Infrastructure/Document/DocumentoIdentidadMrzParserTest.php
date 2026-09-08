<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Document;

use App\Infrastructure\Document\DocumentoIdentidadMrzParser;
use App\Infrastructure\Document\Mrz\MrzCountryResolver;
use App\Infrastructure\Document\Mrz\MrzReader;
use App\Infrastructure\Document\Mrz\NumeroDocumentoEspanol;
use PHPUnit\Framework\TestCase;

final class DocumentoIdentidadMrzParserTest extends TestCase
{
    /** DNI español TD1 con todos los dígitos de control correctos. */
    private const DNI_TD1 = "IDESPBAA000589599999999R<<<<<<\n"
        . "8001014M3001019ESP<<<<<<<<<<<5\n"
        . 'ESPANOLA<ESPANOLA<<CARMEN<<<<<';

    /** Ejemplo canónico ICAO 9303 de pasaporte TD3. */
    private const PASAPORTE_TD3 = "P<UTOERIKSSON<<ANNA<MARIA<<<<<<<<<<<<<<<<<<<\n"
        . 'L898902C36UTO7408122F1204159ZE184226B<<<<<10';

    private DocumentoIdentidadMrzParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DocumentoIdentidadMrzParser(new MrzReader());
    }

    public function testLeeDniEspanolCompleto(): void
    {
        $datos = $this->parser->parseFromText(self::DNI_TD1);

        self::assertNotNull($datos);
        self::assertSame('99999999R', $datos['numDocumento']);
        self::assertSame('DNI', $datos['tipoDocumento']);
        self::assertSame('Española', $datos['nacionalidad']);
        self::assertSame('1980-01-01', $datos['fechaNacimiento']);
        self::assertSame('CARMEN ESPANOLA ESPANOLA', $datos['nombre']);
    }

    /** El fallo reportado: «ESP» leído como «ES7» acababa en el campo nacionalidad. */
    public function testCorrigeCodigoDePaisCorruptoPorOcr(): void
    {
        // «ESP» mal leído tanto en el país emisor (línea 1) como en la nacionalidad (línea 2).
        $corrupta = str_replace(
            ['IDESP', '9ESP<'],
            ['IDES7', '9E5P<'],
            self::DNI_TD1,
        );

        $datos = $this->parser->parseFromText($corrupta);

        self::assertNotNull($datos);
        self::assertSame('Española', $datos['nacionalidad']);
        self::assertSame('99999999R', $datos['numDocumento']);
    }

    public function testNacionalidadVaciaSiElCodigoNoSePuedeResolver(): void
    {
        self::assertSame('ESP', MrzCountryResolver::resolverCodigo('ES7'));
        self::assertSame('ESP', MrzCountryResolver::resolverCodigo('E5P'));
        self::assertSame('Española', MrzCountryResolver::resolverNacionalidad('ES7'));
        // Tres letras limpias fuera de catálogo: no se adivina un país.
        self::assertNull(MrzCountryResolver::resolverCodigo('UTO'));
    }

    public function testToleraRuidoYEspaciosDelOcr(): void
    {
        $sucia = "Documento nacional\n"
            . "IDESP BAA000589 5 99999999R<<<<<<\n"
            . "8001014M3001019ESP<<<<<<<<<<<5\n"
            . "ESPANOLA<ESPANOLA<<CARMEN<<<<<\n"
            . 'IDESP';

        $datos = $this->parser->parseFromText($sucia);

        self::assertNotNull($datos);
        self::assertSame('99999999R', $datos['numDocumento']);
        self::assertSame('1980-01-01', $datos['fechaNacimiento']);
    }

    public function testNoDevuelveElNumeroDeSoporteComoDocumento(): void
    {
        // Misma tarjeta pero con los datos opcionales (el DNI) ilegibles.
        $sinDni = str_replace('99999999R<<<<<<', '<<<<<<<<<<<<<<<', self::DNI_TD1);

        $datos = $this->parser->parseFromText($sinDni);

        if (null !== $datos) {
            self::assertNotSame('BAA000589', $datos['numDocumento']);
        }
    }

    public function testLeePasaporteTd3(): void
    {
        $datos = $this->parser->parseFromText(self::PASAPORTE_TD3);

        self::assertNotNull($datos);
        self::assertSame('PASAPORTE', $datos['tipoDocumento']);
        self::assertSame('L898902C3', $datos['numDocumento']);
        self::assertSame('1974-08-12', $datos['fechaNacimiento']);
        self::assertSame('ANNA MARIA ERIKSSON', $datos['nombre']);
    }

    public function testDescartaTextoSinMrz(): void
    {
        self::assertNull($this->parser->parseFromText('Ministerio del Interior. Documento nacional de identidad.'));
        self::assertNull($this->parser->parseFromText(''));
    }

    public function testLetraDeControlCorrigeLecturasSucias(): void
    {
        self::assertTrue(NumeroDocumentoEspanol::esValido('12345678Z'));
        self::assertFalse(NumeroDocumentoEspanol::esValido('12345678A'));
        self::assertSame('12345678Z', NumeroDocumentoEspanol::corregir('1234S678Z'));
        self::assertSame('12345678Z', NumeroDocumentoEspanol::corregir('I2345678Z'));
        self::assertSame('X1234567L', NumeroDocumentoEspanol::corregir('X123456TL'));
    }
}

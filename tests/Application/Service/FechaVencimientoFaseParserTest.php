<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\FechaVencimientoFaseParser;
use PHPUnit\Framework\TestCase;

final class FechaVencimientoFaseParserTest extends TestCase
{
    private FechaVencimientoFaseParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FechaVencimientoFaseParser();
    }

    public function testRequiereFecha(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe indicar la fecha límite');
        $this->parser->parseRequired(null);
    }

    public function testRechazaFechaInvalida(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fecha de vencimiento no válida');
        $this->parser->parseRequired('32/13/2020');
    }

    public function testRechazaFechaPasada(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no puede ser anterior a hoy');
        $this->parser->parseRequired('2000-01-01');
    }

    public function testAceptaHoyOFuturo(): void
    {
        $hoy = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $parsed = $this->parser->parseRequired($hoy);

        self::assertSame($hoy, $parsed->format('Y-m-d'));
        self::assertSame('23:59:59', $parsed->format('H:i:s'));
    }
}

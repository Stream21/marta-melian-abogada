<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\TipoRequerimientoMercurio;
use PHPUnit\Framework\TestCase;

final class TipoRequerimientoMercurioTest extends TestCase
{
    public function testFromStringAceptaTiposNuevos(): void
    {
        self::assertSame(TipoRequerimientoMercurio::Documentacion, TipoRequerimientoMercurio::fromString('documentacion'));
        self::assertSame(TipoRequerimientoMercurio::Tasas, TipoRequerimientoMercurio::fromString('tasas'));
    }

    public function testFromStringMapeaTiposLegacy(): void
    {
        self::assertSame(TipoRequerimientoMercurio::Documentacion, TipoRequerimientoMercurio::fromString('documento'));
        self::assertSame(TipoRequerimientoMercurio::Documentacion, TipoRequerimientoMercurio::fromString('escrito'));
    }
}

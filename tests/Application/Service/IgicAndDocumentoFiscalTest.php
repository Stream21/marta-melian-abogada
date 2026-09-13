<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\DocumentoFiscalValidator;
use App\Application\Service\IgicInvoiceCalculator;
use PHPUnit\Framework\TestCase;

final class IgicAndDocumentoFiscalTest extends TestCase
{
    public function testExentoNoAnadeImpuesto(): void
    {
        $calc = new IgicInvoiceCalculator(0.0, 'exento');
        $result = $calc->fromTotalWithTax(1000.0);

        self::assertSame(1000.0, $result['subtotal']);
        self::assertSame(0.0, $result['taxAmount']);
        self::assertSame(1000.0, $result['total']);
        self::assertSame('exento', $result['taxKey']);
        self::assertSame(['exento'], $result['taxes']);
        self::assertTrue($calc->isExempt());
    }

    public function testConImpuestoDesglosaTotalIncluido(): void
    {
        $calc = new IgicInvoiceCalculator(7.0, 'igic_7');
        $result = $calc->fromTotalWithTax(1070.0);

        self::assertSame(1000.0, $result['subtotal']);
        self::assertSame(70.0, $result['taxAmount']);
        self::assertSame(1070.0, $result['total']);
        self::assertSame(['igic_7'], $result['taxes']);
    }

    public function testValidaDniYPais(): void
    {
        $v = new DocumentoFiscalValidator();
        $v->assertValid('DNI', '12345678Z', 'ES');
        $this->addToAssertionCount(1);
    }

    public function testRechazaPaisInvalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DocumentoFiscalValidator())->assertValid('PASAPORTE', 'AB123456', 'ESP');
    }

    public function testValidaPasaporte(): void
    {
        $v = new DocumentoFiscalValidator();
        $v->assertValid('PASAPORTE', 'P1234567', 'MA');
        $this->addToAssertionCount(1);
    }
}

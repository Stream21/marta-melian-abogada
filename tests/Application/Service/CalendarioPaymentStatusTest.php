<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\CalendarioCobrosService;
use App\Application\Service\CalendarioPagoService;
use PHPUnit\Framework\TestCase;

final class CalendarioPaymentStatusTest extends TestCase
{
    public function testPartialYPaidDesdeCalendario(): void
    {
        $svc = new CalendarioCobrosService(new CalendarioPagoService());
        $cal = [
            ['numero' => 1, 'importe' => 500.0, 'fechaVencimiento' => '2026-06-01', 'estado' => 'pagado'],
            ['numero' => 2, 'importe' => 500.0, 'fechaVencimiento' => '2026-07-01', 'estado' => 'pendiente'],
        ];

        self::assertSame('partial', $svc->resolverPaymentStatus($cal, 1000.0));

        $cal[1]['estado'] = 'pagado';
        self::assertSame('paid', $svc->resolverPaymentStatus($cal, 1000.0));
    }
}

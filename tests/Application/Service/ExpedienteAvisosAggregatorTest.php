<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\ContratacionPasoValidacionService;
use App\Application\Service\ExpedienteAvisosAggregator;
use App\Domain\Entity\ContratacionPaso;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\PlanPagoExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use PHPUnit\Framework\TestCase;

final class ExpedienteAvisosAggregatorTest extends TestCase
{
    public function testAggregateCombinaContratacionYRequerimientos(): void
    {
        $expSinAvisos = $this->crearExpediente('exp-0');
        $expContratacion = $this->crearExpediente('exp-1');
        $expRequerimientos = $this->crearExpediente('exp-2');
        $expAmbos = $this->crearExpediente('exp-3');

        $contratacionRepository = new class implements ContratacionRepositoryInterface {
            public function savePaso(ContratacionPaso $paso): void
            {
            }

            public function findPasosByExpediente(ExpedienteId $expedienteId): array
            {
                return [];
            }

            public function findPasosByExpedienteIds(array $expedienteIds): array
            {
                $pasoRevision = new ContratacionPaso(
                    'paso-1',
                    new ExpedienteId('exp-1'),
                    PasoContratacionCliente::DatosCliente,
                    EstadoPasoContratacion::RealizadoCliente,
                );
                $pasoAmbos = new ContratacionPaso(
                    'paso-2',
                    new ExpedienteId('exp-3'),
                    PasoContratacionCliente::Firmas,
                    EstadoPasoContratacion::RealizadoCliente,
                );

                return [
                    'exp-1' => [$pasoRevision],
                    'exp-3' => [$pasoAmbos],
                ];
            }

            public function findPaso(ExpedienteId $expedienteId, PasoContratacionCliente $paso): ?ContratacionPaso
            {
                return null;
            }

            public function saveHito(\App\Domain\Entity\ExpedienteHito $hito): void
            {
            }

            public function findHitoById(string $id): ?\App\Domain\Entity\ExpedienteHito
            {
                return null;
            }

            public function findHitosByExpediente(ExpedienteId $expedienteId): array
            {
                return [];
            }

            public function findRecentHitos(int $limit = 20): array
            {
                return [];
            }

            public function findHitosByExpedientePaginated(ExpedienteId $expedienteId, int $offset, int $limit): array
            {
                return [];
            }

            public function countHitosByExpediente(ExpedienteId $expedienteId): int
            {
                return 0;
            }

            public function marcarHitoLeido(string $hitoId): void
            {
            }

            public function findHitosLeidosIds(): array
            {
                return [];
            }
        };

        $documentoRepository = new class implements ExpedienteDocumentoRepositoryInterface {
            public function save(\App\Domain\Entity\ExpedienteDocumentoEntregado $documento): void
            {
            }

            public function findByExpediente(ExpedienteId $expedienteId): array
            {
                return [];
            }

            public function countPendientesRevisionByExpedienteIds(array $expedienteIds): array
            {
                return [
                    'exp-2' => 2,
                    'exp-3' => 1,
                ];
            }

            public function findByExpedienteAndDocumento(
                ExpedienteId $expedienteId,
                \App\Domain\ValueObject\TramiteDocumentoRequeridoId $documentoRequeridoId,
            ): ?\App\Domain\Entity\ExpedienteDocumentoEntregado {
                return null;
            }

            public function findByExpedienteAndExpedienteDocumento(
                ExpedienteId $expedienteId,
                \App\Domain\ValueObject\ExpedienteDocumentoRequeridoId $expedienteDocumentoRequeridoId,
            ): ?\App\Domain\Entity\ExpedienteDocumentoEntregado {
                return null;
            }

            public function deleteById(string $id): void
            {
            }
        };

        $aggregator = new ExpedienteAvisosAggregator(
            $contratacionRepository,
            $documentoRepository,
            new ContratacionPasoValidacionService(),
        );

        $result = $aggregator->aggregate([$expSinAvisos, $expContratacion, $expRequerimientos, $expAmbos]);

        self::assertSame(
            ['contratacion' => 0, 'requerimientos' => 0, 'total' => 0],
            $result['exp-0'],
        );
        self::assertSame(
            ['contratacion' => 1, 'requerimientos' => 0, 'total' => 1],
            $result['exp-1'],
        );
        self::assertSame(
            ['contratacion' => 0, 'requerimientos' => 2, 'total' => 2],
            $result['exp-2'],
        );
        self::assertSame(
            ['contratacion' => 1, 'requerimientos' => 1, 'total' => 2],
            $result['exp-3'],
        );
    }

    public function testAggregateDevuelveArrayVacioSinExpedientes(): void
    {
        $aggregator = new ExpedienteAvisosAggregator(
            $this->createMock(ContratacionRepositoryInterface::class),
            $this->createMock(ExpedienteDocumentoRepositoryInterface::class),
            new ContratacionPasoValidacionService(),
        );

        self::assertSame([], $aggregator->aggregate([]));
    }

    private function crearExpediente(string $id): Expediente
    {
        return Expediente::crearAlta(
            new ExpedienteId($id),
            'EXP-2026/0001',
            'Expediente test',
            'Cliente Test',
            '/tmp/exp',
            'cliente-1',
            'tramite-1',
            'servicio-1',
            1000.0,
            MetodoPagoExpediente::Digital,
            PlanPagoExpediente::Unico,
            1,
            'token-' . $id,
        );
    }
}

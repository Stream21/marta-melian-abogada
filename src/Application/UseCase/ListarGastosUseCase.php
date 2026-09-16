<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Gasto;
use App\Domain\Repository\GastoRepositoryInterface;

final class ListarGastosUseCase
{
    public function __construct(
        private GastoRepositoryInterface $gastoRepository,
    ) {
    }

    /**
     * @param array{
     *   fechaDesde?: string,
     *   fechaHasta?: string,
     *   q?: string,
     *   categoria?: string
     * } $filters
     *
     * @return array{
     *   items: list<array<string, mixed>>,
     *   kpis: array{totalPeriodo: float, cantidad: int, totalMesActual: float}
     * }
     */
    public function __invoke(array $filters = []): array
    {
        $gastos = $this->gastoRepository->findAll();
        $now = new \DateTimeImmutable('now');
        $mesInicio = $now->modify('first day of this month')->setTime(0, 0, 0);
        $mesFin = $now->modify('last day of this month')->setTime(23, 59, 59);

        $fechaDesde = $this->parseOptionalDate((string) ($filters['fechaDesde'] ?? ''), false);
        $fechaHasta = $this->parseOptionalDate((string) ($filters['fechaHasta'] ?? ''), true);
        $q = mb_strtolower(trim((string) ($filters['q'] ?? '')));
        $categoria = mb_strtolower(trim((string) ($filters['categoria'] ?? '')));

        $totalMesActual = 0.0;
        $totalPeriodo = 0.0;
        $items = [];

        foreach ($gastos as $gasto) {
            $importe = (float) $gasto->importe();
            $fecha = $gasto->fecha()->setTime(0, 0, 0);

            if ($fecha >= $mesInicio && $fecha <= $mesFin) {
                $totalMesActual += $importe;
            }

            if (null !== $fechaDesde && $fecha < $fechaDesde) {
                continue;
            }
            if (null !== $fechaHasta && $fecha > $fechaHasta) {
                continue;
            }

            if ('' !== $categoria) {
                $cat = mb_strtolower((string) ($gasto->categoria() ?? ''));
                if ($cat !== $categoria && !str_contains($cat, $categoria)) {
                    continue;
                }
            }

            if ('' !== $q) {
                $haystack = mb_strtolower(
                    $gasto->concepto() . ' ' . ($gasto->categoria() ?? '') . ' ' . ($gasto->notas() ?? ''),
                );
                if (!str_contains($haystack, $q)) {
                    continue;
                }
            }

            $totalPeriodo += $importe;
            $items[] = $this->toArray($gasto);
        }

        return [
            'items' => $items,
            'kpis' => [
                'totalPeriodo' => round($totalPeriodo, 2),
                'cantidad' => count($items),
                'totalMesActual' => round($totalMesActual, 2),
            ],
        ];
    }

    private function parseOptionalDate(string $value, bool $endOfDay): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (false === $dt) {
            throw new \InvalidArgumentException('Las fechas de filtro deben tener formato YYYY-MM-DD.');
        }

        return $endOfDay ? $dt->setTime(23, 59, 59) : $dt->setTime(0, 0, 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Gasto $gasto): array
    {
        return [
            'id' => $gasto->id()->value(),
            'concepto' => $gasto->concepto(),
            'importe' => $gasto->importe(),
            'fecha' => $gasto->fecha()->format('Y-m-d'),
            'categoria' => $gasto->categoria(),
            'notas' => $gasto->notas(),
            'tieneFactura' => $gasto->tieneFactura(),
            'facturaUrl' => $gasto->facturaUrl(),
            'createdAt' => $gasto->createdAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $gasto->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}

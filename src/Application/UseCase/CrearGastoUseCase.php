<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Gasto;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\ValueObject\GastoId;

final class CrearGastoUseCase
{
    public function __construct(
        private GastoRepositoryInterface $gastoRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(
        string $concepto,
        string $importe,
        string $fecha,
        ?string $categoria = null,
        ?string $notas = null,
    ): array {
        $fechaDt = $this->parseFecha($fecha);
        $now = new \DateTimeImmutable('now');

        $gasto = new Gasto(
            GastoId::generate(),
            $concepto,
            $importe,
            $fechaDt,
            $categoria,
            $notas,
            null,
            $now,
            $now,
        );

        $this->gastoRepository->save($gasto);

        return $this->toArray($gasto);
    }

    private function parseFecha(string $fecha): \DateTimeImmutable
    {
        $fecha = trim($fecha);
        if ('' === $fecha) {
            throw new \InvalidArgumentException('La fecha del gasto es obligatoria.');
        }

        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if (false === $dt) {
            throw new \InvalidArgumentException('La fecha debe tener formato YYYY-MM-DD.');
        }

        return $dt;
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

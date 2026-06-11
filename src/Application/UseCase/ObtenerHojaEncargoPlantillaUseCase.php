<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\HojaEncargoDefaultPlantillaFactory;
use App\Domain\Repository\HojaEncargoPlantillaRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class ObtenerHojaEncargoPlantillaUseCase
{
    public function __construct(
        private HojaEncargoPlantillaRepositoryInterface $plantillaRepository,
        private TramiteRepositoryInterface $tramiteRepository,
    ) {
    }

    /**
     * @return array{tramiteId: string, esDefault: bool, bloques: list<array<string, mixed>>}
     */
    public function __invoke(string $tramiteId): array
    {
        $id = new TramiteId($tramiteId);
        $tramite = $this->tramiteRepository->findById($id);
        if (null === $tramite) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $plantilla = $this->plantillaRepository->findByTramiteId($id);
        if (null === $plantilla) {
            return [
                'tramiteId' => $tramiteId,
                'esDefault' => true,
                'bloques' => HojaEncargoDefaultPlantillaFactory::createDefault(),
            ];
        }

        return [
            'tramiteId' => $tramiteId,
            'esDefault' => false,
            'bloques' => $plantilla->bloques(),
        ];
    }
}

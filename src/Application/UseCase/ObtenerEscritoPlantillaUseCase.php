<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\EscritoDefaultPlantillaFactory;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\EscritoPlantillaRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class ObtenerEscritoPlantillaUseCase
{
    public function __construct(
        private EscritoPlantillaRepositoryInterface $plantillaRepository,
        private TramiteRepositoryInterface $tramiteRepository,
    ) {
    }

    /**
     * @return array{
     *     tramiteId: string,
     *     tipo: string,
     *     esDefault: bool,
     *     esPlantillaGlobal: bool,
     *     bloques: list<array<string, mixed>>
     * }
     */
    public function __invoke(string $tramiteId, string $tipo): array
    {
        $id = new TramiteId($tramiteId);
        $tramite = $this->tramiteRepository->findById($id);
        if (null === $tramite) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $tipoEscrito = TipoEscrito::fromString($tipo);
        $plantilla = $this->plantillaRepository->findByTramiteAndTipo($id, $tipoEscrito);

        if (null !== $plantilla) {
            return [
                'tramiteId' => $tramiteId,
                'tipo' => $tipoEscrito->value,
                'esDefault' => false,
                'esPlantillaGlobal' => false,
                'bloques' => $plantilla->bloques(),
            ];
        }

        $esPlantillaGlobal = TipoEscrito::Designacion === $tipoEscrito || TipoEscrito::Rgpd === $tipoEscrito;

        return [
            'tramiteId' => $tramiteId,
            'tipo' => $tipoEscrito->value,
            'esDefault' => true,
            'esPlantillaGlobal' => $esPlantillaGlobal,
            'bloques' => EscritoDefaultPlantillaFactory::createDefault($tipoEscrito),
        ];
    }
}

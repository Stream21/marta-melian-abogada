<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\GuardarEscritoPlantillaInput;
use App\Application\Service\EscritoBloqueValidator;
use App\Domain\Entity\EscritoPlantilla;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\EscritoPlantillaRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\EscritoPlantillaId;
use App\Domain\ValueObject\TramiteId;

final class GuardarEscritoPlantillaUseCase
{
    public function __construct(
        private EscritoPlantillaRepositoryInterface $plantillaRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private EscritoBloqueValidator $bloqueValidator,
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
    public function __invoke(GuardarEscritoPlantillaInput $input): array
    {
        $tramiteId = new TramiteId($input->tramiteId);
        $tramite = $this->tramiteRepository->findById($tramiteId);
        if (null === $tramite) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $tipoEscrito = TipoEscrito::fromString($input->tipo);
        $bloques = $this->bloqueValidator->validateAndNormalize($input->bloques);

        $existing = $this->plantillaRepository->findByTramiteAndTipo($tramiteId, $tipoEscrito);
        if (null === $existing) {
            $plantilla = new EscritoPlantilla(
                EscritoPlantillaId::generate(),
                $tramiteId,
                $tipoEscrito,
                $bloques,
            );
        } else {
            $plantilla = $existing->withBloques($bloques);
        }

        $this->plantillaRepository->save($plantilla);

        return [
            'tramiteId' => $input->tramiteId,
            'tipo' => $tipoEscrito->value,
            'esDefault' => false,
            'esPlantillaGlobal' => false,
            'bloques' => $bloques,
        ];
    }
}

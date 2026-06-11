<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\GuardarEscritoPlantillaInput;
use App\Application\Service\EscritoVariableCatalog;
use App\Application\UseCase\GuardarEscritoPlantillaUseCase;
use App\Application\UseCase\ObtenerEscritoPlantillaUseCase;
use App\Domain\Entity\TipoEscrito;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** @deprecated Use EscritoController — kept for backward compatibility */
#[Route(name: 'api_hoja_encargo_')]
final class HojaEncargoController extends AbstractController
{
    public function __construct(
        private EscritoVariableCatalog $variableCatalog,
        private ObtenerEscritoPlantillaUseCase $obtenerPlantilla,
        private GuardarEscritoPlantillaUseCase $guardarPlantilla,
    ) {
    }

    #[Route(path: '/api/hoja-encargo/variables', name: 'variables', methods: ['GET'])]
    public function variables(): JsonResponse
    {
        return new JsonResponse($this->variableCatalog->all());
    }

    #[Route(path: '/api/tramites/{tramiteId}/hoja-encargo', name: 'get', methods: ['GET'])]
    public function get(string $tramiteId): JsonResponse
    {
        try {
            $result = ($this->obtenerPlantilla)($tramiteId, TipoEscrito::HojaEncargo->value);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($result);
    }

    #[Route(path: '/api/tramites/{tramiteId}/hoja-encargo', name: 'put', methods: ['PUT'])]
    public function put(string $tramiteId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['bloques']) || !is_array($data['bloques'])) {
            return new JsonResponse(['message' => 'Se requiere un array "bloques".'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = ($this->guardarPlantilla)(new GuardarEscritoPlantillaInput(
                $tramiteId,
                TipoEscrito::HojaEncargo->value,
                $data['bloques'],
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($result);
    }
}

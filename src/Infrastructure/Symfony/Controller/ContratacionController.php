<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\ObtenerContratacionExpedienteUseCase;
use App\Application\UseCase\ValidarPasoContratacionUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/expedientes/{id}/contratacion', name: 'api_expedientes_contratacion_')]
final class ContratacionController extends AbstractController
{
    public function __construct(
        private ObtenerContratacionExpedienteUseCase $obtenerContratacion,
        private ValidarPasoContratacionUseCase $validarPaso,
    ) {
    }

    #[Route(path: '', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerContratacion)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/validar/{paso}', name: 'validar', methods: ['POST'])]
    public function validar(string $id, string $paso): JsonResponse
    {
        try {
            ($this->validarPaso)($id, $paso);

            return new JsonResponse(($this->obtenerContratacion)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

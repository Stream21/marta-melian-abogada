<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\ObtenerAccesoExpedienteUseCase;
use App\Application\UseCase\RegistrarHitoClienteContratacionUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/acceso', name: 'api_acceso_')]
final class AccesoExpedienteController extends AbstractController
{
    public function __construct(
        private ObtenerAccesoExpedienteUseCase $obtenerAcceso,
        private RegistrarHitoClienteContratacionUseCase $registrarHitoCliente,
    ) {
    }

    #[Route(path: '/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{token}/completar-paso', name: 'completar_paso', methods: ['POST'])]
    public function completarPaso(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $paso = (string) ($data['paso'] ?? '');

        try {
            ($this->registrarHitoCliente)($token, $paso);

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

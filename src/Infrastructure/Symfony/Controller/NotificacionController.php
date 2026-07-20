<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\MarcarNotificacionLeidaUseCase;
use App\Application\UseCase\MarcarTodasNotificacionesLeidasUseCase;
use App\Application\UseCase\ObtenerNotificacionesRecientesUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/notificaciones', name: 'api_notificaciones_')]
final class NotificacionController extends AbstractController
{
    public function __construct(
        private ObtenerNotificacionesRecientesUseCase $obtenerNotificaciones,
        private MarcarNotificacionLeidaUseCase $marcarLeida,
        private MarcarTodasNotificacionesLeidasUseCase $marcarTodasLeidas,
    ) {
    }

    #[Route(path: '/recientes', name: 'recientes', methods: ['GET'])]
    public function recientes(): JsonResponse
    {
        return new JsonResponse(($this->obtenerNotificaciones)());
    }

    #[Route(path: '/leidas', name: 'leidas_todas', methods: ['POST'])]
    public function marcarTodasLeidas(): JsonResponse
    {
        $marcadas = ($this->marcarTodasLeidas)();

        return new JsonResponse(['ok' => true, 'marcadas' => $marcadas]);
    }

    #[Route(path: '/{hitoId}/leida', name: 'leida', methods: ['POST'])]
    public function marcarLeida(string $hitoId): JsonResponse
    {
        try {
            ($this->marcarLeida)($hitoId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(['ok' => true]);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\ObtenerNotificacionesRecientesUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/notificaciones', name: 'api_notificaciones_')]
final class NotificacionController extends AbstractController
{
    public function __construct(
        private ObtenerNotificacionesRecientesUseCase $obtenerNotificaciones,
    ) {
    }

    #[Route(path: '/recientes', name: 'recientes', methods: ['GET'])]
    public function recientes(): JsonResponse
    {
        return new JsonResponse(($this->obtenerNotificaciones)());
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\ObtenerDashboardKpisUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/dashboard', name: 'api_dashboard_')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private ObtenerDashboardKpisUseCase $obtenerDashboardKpis,
    ) {
    }

    #[Route(path: '', name: 'kpis', methods: ['GET'])]
    public function kpis(): JsonResponse
    {
        return new JsonResponse(($this->obtenerDashboardKpis)());
    }
}

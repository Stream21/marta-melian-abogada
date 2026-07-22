<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\ListarNacionalidadesUseCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/catalogos', name: 'api_catalogos_')]
final class CatalogoController
{
    public function __construct(
        private ListarNacionalidadesUseCase $listarNacionalidades,
    ) {
    }

    #[Route(path: '/nacionalidades', name: 'nacionalidades', methods: ['GET'])]
    public function nacionalidades(): JsonResponse
    {
        return new JsonResponse(($this->listarNacionalidades)());
    }
}

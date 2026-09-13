<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\GuardarCamposFormularioServicioUseCase;
use App\Application\UseCase\ListarCamposFormularioServicioUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/servicios/{servicioId}/campos-formulario', name: 'api_servicio_campos_formulario_')]
final class ServicioCampoFormularioController extends AbstractController
{
    public function __construct(
        private ListarCamposFormularioServicioUseCase $listar,
        private GuardarCamposFormularioServicioUseCase $guardar,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(string $servicioId): JsonResponse
    {
        try {
            $campos = ($this->listar)($servicioId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['campos' => $campos]);
    }

    #[Route(path: '', name: 'put', methods: ['PUT'])]
    public function put(string $servicioId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['campos']) || !is_array($data['campos'])) {
            return new JsonResponse(['message' => 'Se requiere un array "campos".'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $campos = ($this->guardar)($servicioId, $data['campos']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['campos' => $campos]);
    }
}

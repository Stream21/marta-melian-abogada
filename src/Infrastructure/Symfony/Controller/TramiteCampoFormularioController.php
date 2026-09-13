<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\GuardarCamposFormularioTramiteUseCase;
use App\Application\UseCase\ListarCamposFormularioTramiteUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/tramites/{tramiteId}/campos-formulario', name: 'api_tramite_campos_formulario_')]
final class TramiteCampoFormularioController extends AbstractController
{
    public function __construct(
        private ListarCamposFormularioTramiteUseCase $listar,
        private GuardarCamposFormularioTramiteUseCase $guardar,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(string $tramiteId): JsonResponse
    {
        try {
            $campos = ($this->listar)($tramiteId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['campos' => $campos]);
    }

    #[Route(path: '', name: 'put', methods: ['PUT'])]
    public function put(string $tramiteId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['campos']) || !is_array($data['campos'])) {
            return new JsonResponse(['message' => 'Se requiere un array "campos".'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $campos = ($this->guardar)($tramiteId, $data['campos']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['campos' => $campos]);
    }
}

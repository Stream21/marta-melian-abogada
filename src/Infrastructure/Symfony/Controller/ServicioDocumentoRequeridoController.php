<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\GuardarDocumentosRequeridosServicioInput;
use App\Application\UseCase\GuardarDocumentosRequeridosServicioUseCase;
use App\Application\UseCase\ListarDocumentosRequeridosServicioUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/servicios/{servicioId}/documentos-requeridos', name: 'api_servicio_documentos_requeridos_')]
final class ServicioDocumentoRequeridoController extends AbstractController
{
    public function __construct(
        private ListarDocumentosRequeridosServicioUseCase $listar,
        private GuardarDocumentosRequeridosServicioUseCase $guardar,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(string $servicioId): JsonResponse
    {
        try {
            $documentos = ($this->listar)($servicioId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['documentos' => $documentos]);
    }

    #[Route(path: '', name: 'put', methods: ['PUT'])]
    public function put(string $servicioId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['documentos']) || !is_array($data['documentos'])) {
            return new JsonResponse(['message' => 'Se requiere un array "documentos".'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $documentos = ($this->guardar)(new GuardarDocumentosRequeridosServicioInput($servicioId, $data['documentos']));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['documentos' => $documentos]);
    }
}

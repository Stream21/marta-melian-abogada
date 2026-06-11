<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\GuardarDocumentosRequeridosInput;
use App\Application\UseCase\GuardarDocumentosRequeridosUseCase;
use App\Application\UseCase\ListarDocumentosRequeridosUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/tramites/{tramiteId}/documentos-requeridos', name: 'api_tramite_documentos_requeridos_')]
final class TramiteDocumentoRequeridoController extends AbstractController
{
    public function __construct(
        private ListarDocumentosRequeridosUseCase $listar,
        private GuardarDocumentosRequeridosUseCase $guardar,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(string $tramiteId): JsonResponse
    {
        try {
            $documentos = ($this->listar)($tramiteId);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['documentos' => $documentos]);
    }

    #[Route(path: '', name: 'put', methods: ['PUT'])]
    public function put(string $tramiteId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['documentos']) || !is_array($data['documentos'])) {
            return new JsonResponse(['message' => 'Se requiere un array "documentos".'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $documentos = ($this->guardar)(new GuardarDocumentosRequeridosInput($tramiteId, $data['documentos']));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['documentos' => $documentos]);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\ArchivarNotaExpedienteUseCase;
use App\Application\UseCase\CrearNotaExpedienteUseCase;
use App\Application\UseCase\ListarNotasExpedienteUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/expedientes/{id}/notas', name: 'api_expedientes_notas_')]
#[IsGranted('ROLE_USER')]
final class ExpedienteNotaController extends AbstractController
{
    public function __construct(
        private ListarNotasExpedienteUseCase $listarNotas,
        private CrearNotaExpedienteUseCase $crearNota,
        private ArchivarNotaExpedienteUseCase $archivarNota,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->listarNotas)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = [];
        }

        try {
            $nota = ($this->crearNota)($id, (string) ($data['contenido'] ?? ''));

            return new JsonResponse($nota, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'no encontrado')
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_BAD_REQUEST;

            return new JsonResponse(['message' => $e->getMessage()], $status);
        }
    }

    #[Route(path: '/{notaId}/archivar', name: 'archivar', methods: ['POST'])]
    public function archivar(string $id, string $notaId): JsonResponse
    {
        try {
            return new JsonResponse(($this->archivarNota)($id, $notaId, true));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{notaId}/desarchivar', name: 'desarchivar', methods: ['POST'])]
    public function desarchivar(string $id, string $notaId): JsonResponse
    {
        try {
            return new JsonResponse(($this->archivarNota)($id, $notaId, false));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}

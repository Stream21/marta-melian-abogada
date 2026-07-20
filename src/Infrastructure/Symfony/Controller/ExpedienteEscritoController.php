<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\UseCase\ActualizarEscritoExpedienteUseCase;
use App\Application\UseCase\GuardarEscritoExpedienteUseCase;
use App\Application\UseCase\ListarEscritosExpedienteUseCase;
use App\Application\UseCase\ObtenerEscritoExpedienteUseCase;
use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/expedientes/{id}/escritos', name: 'api_expedientes_escritos_')]
final class ExpedienteEscritoController extends AbstractController
{
    public function __construct(
        private ListarEscritosExpedienteUseCase $listarEscritos,
        private ObtenerEscritoExpedienteUseCase $obtenerEscrito,
        private GuardarEscritoExpedienteUseCase $guardarEscrito,
        private ActualizarEscritoExpedienteUseCase $actualizarEscrito,
        private ExpedienteEscritoRepositoryInterface $escritoRepository,
        private ExpedienteFileStoragePort $fileStorage,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->listarEscritos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{escritoId}', name: 'show', methods: ['GET'])]
    public function show(string $id, string $escritoId): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerEscrito)($id, $escritoId));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $result = ($this->guardarEscrito)(
                $id,
                (string) ($data['titulo'] ?? ''),
                (string) ($data['contenidoHtml'] ?? ''),
            );

            return new JsonResponse($result, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{escritoId}', name: 'update', methods: ['PUT'])]
    public function update(string $id, string $escritoId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $result = ($this->actualizarEscrito)(
                $id,
                $escritoId,
                (string) ($data['titulo'] ?? ''),
                (string) ($data['contenidoHtml'] ?? ''),
            );

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{escritoId}/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(string $id, string $escritoId): Response
    {
        $escrito = $this->escritoRepository->findById($escritoId);
        if (null === $escrito || !$escrito->expedienteId()->equals(new ExpedienteId($id))) {
            return new JsonResponse(['message' => 'Escrito no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = $this->fileStorage->readRelativePath($escrito->pdfPath());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="escrito.pdf"',
        ]);
    }
}

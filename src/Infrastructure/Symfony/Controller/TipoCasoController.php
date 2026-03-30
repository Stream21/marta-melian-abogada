<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\ActualizarTipoCasoInput;
use App\Application\DTO\CrearTipoCasoInput;
use App\Application\UseCase\ActualizarTipoCasoUseCase;
use App\Application\UseCase\CrearTipoCasoUseCase;
use App\Application\UseCase\EliminarTipoCasoUseCase;
use App\Application\UseCase\ListarTiposCasoUseCase;
use App\Domain\Entity\TipoCaso;
use App\Domain\Repository\TipoCasoRepositoryInterface;
use App\Domain\ValueObject\TipoCasoId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/tipos-caso', name: 'api_tipos_caso_')]
final class TipoCasoController extends AbstractController
{
    public function __construct(
        private ListarTiposCasoUseCase $listar,
        private CrearTipoCasoUseCase $crear,
        private ActualizarTipoCasoUseCase $actualizar,
        private EliminarTipoCasoUseCase $eliminar,
        private TipoCasoRepositoryInterface $repository,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $tipos = ($this->listar)();

        return new JsonResponse(array_map($this->toArray(...), $tipos));
    }

    #[Route(path: '/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $tipo = $this->repository->findById(new TipoCasoId($id));
        if (null === $tipo) {
            return new JsonResponse(['message' => 'Tipo de caso no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->toArray($tipo));
    }

    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['message' => 'JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tipo = ($this->crear)(new CrearTipoCasoInput(
                nombre: $data['nombre'] ?? '',
                descripcion: $data['descripcion'] ?? '',
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->toArray($tipo), Response::HTTP_CREATED);
    }

    #[Route(path: '/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['message' => 'JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tipo = ($this->actualizar)(new ActualizarTipoCasoInput(
                id: $id,
                nombre: $data['nombre'] ?? '',
                descripcion: $data['descripcion'] ?? '',
            ));
        } catch (\InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'no encontrado') ? Response::HTTP_NOT_FOUND : Response::HTTP_BAD_REQUEST;

            return new JsonResponse(['message' => $e->getMessage()], $code);
        }

        return new JsonResponse($this->toArray($tipo));
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): Response
    {
        try {
            ($this->eliminar)($id);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(TipoCaso $t): array
    {
        return [
            'id' => $t->id()->value(),
            'nombre' => $t->nombre(),
            'descripcion' => $t->descripcion(),
        ];
    }
}

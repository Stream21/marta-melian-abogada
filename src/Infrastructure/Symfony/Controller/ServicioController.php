<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\ActualizarServicioInput;
use App\Application\DTO\CrearServicioInput;
use App\Application\UseCase\ActualizarServicioUseCase;
use App\Application\UseCase\CambiarEstadoServicioUseCase;
use App\Application\UseCase\CrearServicioUseCase;
use App\Application\UseCase\ListarServiciosUseCase;
use App\Domain\Entity\Servicio;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\ValueObject\ServicioId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/servicios', name: 'api_servicios_')]
final class ServicioController extends AbstractController
{
    public function __construct(
        private ListarServiciosUseCase $listar,
        private CrearServicioUseCase $crear,
        private ActualizarServicioUseCase $actualizar,
        private CambiarEstadoServicioUseCase $cambiarEstado,
        private ServicioRepositoryInterface $repository,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $incluirInactivos = $request->query->getBoolean('incluir_inactivos');
        $servicios = ($this->listar)($incluirInactivos);

        return new JsonResponse(array_map($this->toArray(...), $servicios));
    }

    #[Route(path: '/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $servicio = $this->repository->findById(new ServicioId($id));
        if (null === $servicio) {
            return new JsonResponse(['message' => 'Servicio no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->toArray($servicio));
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
            $servicio = ($this->crear)(new CrearServicioInput(
                nombre: $data['nombre'] ?? '',
                tipo: $data['tipo'] ?? '',
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->toArray($servicio), Response::HTTP_CREATED);
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
            $servicio = ($this->actualizar)(new ActualizarServicioInput(
                id: $id,
                nombre: $data['nombre'] ?? '',
                tipo: $data['tipo'] ?? '',
            ));
        } catch (\InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'no encontrado') ? Response::HTTP_NOT_FOUND : Response::HTTP_BAD_REQUEST;

            return new JsonResponse(['message' => $e->getMessage()], $code);
        }

        return new JsonResponse($this->toArray($servicio));
    }

    #[Route(path: '/{id}/estado', name: 'estado', methods: ['PATCH'])]
    public function cambiarEstado(string $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new JsonResponse(['message' => 'JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        if (!array_key_exists('activo', $data) || !is_bool($data['activo'])) {
            return new JsonResponse(['message' => 'El campo activo (boolean) es obligatorio.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $servicio = ($this->cambiarEstado)($id, $data['activo']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->toArray($servicio));
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Servicio $s): array
    {
        return [
            'id' => $s->id()->value(),
            'nombre' => $s->nombre(),
            'tipo' => $s->tipo()->value,
            'tipoLabel' => $s->tipo()->label(),
            'activo' => $s->activo(),
            'createdAt' => $s->createdAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $s->updatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}

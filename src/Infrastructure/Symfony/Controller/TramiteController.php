<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\ActualizarTramiteInput;
use App\Application\DTO\CrearTramiteInput;
use App\Application\UseCase\ActualizarTramiteUseCase;
use App\Application\UseCase\CambiarEstadoTramiteUseCase;
use App\Application\UseCase\CrearTramiteUseCase;
use App\Application\UseCase\ListarTramitesUseCase;
use App\Domain\Entity\Tramite;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/tramites', name: 'api_tramites_')]
final class TramiteController extends AbstractController
{
    public function __construct(
        private ListarTramitesUseCase $listar,
        private CrearTramiteUseCase $crear,
        private ActualizarTramiteUseCase $actualizar,
        private CambiarEstadoTramiteUseCase $cambiarEstado,
        private TramiteRepositoryInterface $repository,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $incluirInactivos = $request->query->getBoolean('incluir_inactivos');
        $servicioId = $request->query->getString('servicio_id');
        $tramites = ($this->listar)($incluirInactivos, '' !== $servicioId ? $servicioId : null);

        return new JsonResponse(array_map($this->toArray(...), $tramites));
    }

    #[Route(path: '/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $tramite = $this->repository->findById(new TramiteId($id));
        if (null === $tramite) {
            return new JsonResponse(['message' => 'Trámite no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->toArray($tramite));
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
            $tramite = ($this->crear)(new CrearTramiteInput(
                servicioId: $data['servicioId'] ?? '',
                nombre: $data['nombre'] ?? '',
                honorarios: (float) ($data['honorarios'] ?? 0),
                plataforma: (string) ($data['plataforma'] ?? 'mercurio'),
                requiereProcurador: (bool) ($data['requiereProcurador'] ?? false),
            ));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->toArray($tramite), Response::HTTP_CREATED);
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
            $tramite = ($this->actualizar)(new ActualizarTramiteInput(
                id: $id,
                servicioId: $data['servicioId'] ?? '',
                nombre: $data['nombre'] ?? '',
                honorarios: (float) ($data['honorarios'] ?? 0),
                plataforma: (string) ($data['plataforma'] ?? 'mercurio'),
                requiereProcurador: (bool) ($data['requiereProcurador'] ?? false),
            ));
        } catch (\InvalidArgumentException $e) {
            $code = str_contains($e->getMessage(), 'no encontrado') ? Response::HTTP_NOT_FOUND : Response::HTTP_BAD_REQUEST;

            return new JsonResponse(['message' => $e->getMessage()], $code);
        }

        return new JsonResponse($this->toArray($tramite));
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
            $tramite = ($this->cambiarEstado)($id, $data['activo']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->toArray($tramite));
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Tramite $t): array
    {
        return [
            'id' => $t->id()->value(),
            'servicioId' => $t->servicioId()->value(),
            'servicioNombre' => $t->servicioNombre(),
            'nombre' => $t->nombre(),
            'honorarios' => $t->honorarios(),
            'plataforma' => $t->plataforma()->value,
            'plataformaLabel' => $t->plataforma()->label(),
            'requiereProcurador' => $t->requiereProcurador(),
            'activo' => $t->activo(),
            'createdAt' => $t->createdAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $t->updatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}

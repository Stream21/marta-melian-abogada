<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\ClienteInput;
use App\Application\UseCase\BuscarClientesUseCase;
use App\Application\UseCase\GuardarClienteUseCase;
use App\Application\UseCase\ListarClientesUseCase;
use App\Domain\Exception\TelefonoClienteDuplicadoException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/clientes', name: 'api_clientes_')]
final class ClienteController extends AbstractController
{
    public function __construct(
        private ListarClientesUseCase $listar,
        private GuardarClienteUseCase $guardar,
        private BuscarClientesUseCase $buscarClientes,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(($this->listar)());
    }

    #[Route(path: '/buscar', name: 'buscar', methods: ['GET'])]
    public function buscar(Request $request): JsonResponse
    {
        $query = (string) ($request->query->get('q') ?? $request->query->get('telefono', ''));

        return new JsonResponse(($this->buscarClientes)($query));
    }

    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        return $this->save(null, $request);
    }

    #[Route(path: '/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        return $this->save($id, $request);
    }

    private function save(?string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $cliente = ($this->guardar)($id, new ClienteInput(
                (string) ($data['nombre'] ?? ''),
                (string) ($data['nacionalidad'] ?? ''),
                (string) ($data['tipoDocumento'] ?? ''),
                (string) ($data['numDocumento'] ?? ''),
                isset($data['fechaNacimiento']) ? (string) $data['fechaNacimiento'] : null,
                (string) ($data['lugarNacimiento'] ?? ''),
                (string) ($data['domicilio'] ?? ''),
                (string) ($data['codigoPostal'] ?? ''),
                (string) ($data['ciudad'] ?? ''),
                (string) ($data['telefono'] ?? ''),
                (string) ($data['email'] ?? ''),
            ));
        } catch (TelefonoClienteDuplicadoException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'clienteExistenteId' => $e->clienteExistenteId,
                'clienteExistenteNombre' => $e->clienteExistenteNombre,
            ], Response::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'id' => $cliente->id()->value(),
            'nombre' => $cliente->nombre(),
            'nacionalidad' => $cliente->nacionalidad(),
            'tipoDocumento' => $cliente->tipoDocumento(),
            'numDocumento' => $cliente->numDocumento(),
            'fechaNacimiento' => $cliente->fechaNacimiento()?->format('Y-m-d'),
            'lugarNacimiento' => $cliente->lugarNacimiento(),
            'domicilio' => $cliente->domicilio(),
            'codigoPostal' => $cliente->codigoPostal(),
            'ciudad' => $cliente->ciudad(),
            'telefono' => $cliente->telefono(),
            'email' => $cliente->email(),
        ], null === $id ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}

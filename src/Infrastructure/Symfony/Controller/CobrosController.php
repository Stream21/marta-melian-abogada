<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\ListarCobrosGlobalesUseCase;
use App\Application\UseCase\SincronizarPagoHoldedUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/cobros', name: 'api_cobros_')]
final class CobrosController extends AbstractController
{
    public function __construct(
        private ListarCobrosGlobalesUseCase $listarCobros,
        private SincronizarPagoHoldedUseCase $sincronizarPagoHolded,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $result = ($this->listarCobros)([
            'estadoCobro' => $request->query->getString('estadoCobro'),
            'holdedEstado' => $request->query->getString('holdedEstado'),
            'tipo' => $request->query->getString('tipo'),
            'desde' => $request->query->getString('desde'),
            'hasta' => $request->query->getString('hasta'),
            'q' => $request->query->getString('q'),
        ]);

        return new JsonResponse($result);
    }

    #[Route(path: '/{id}/sincronizar-holded', name: 'sincronizar_holded', methods: ['POST'])]
    public function sincronizarHolded(string $id): JsonResponse
    {
        try {
            $result = ($this->sincronizarPagoHolded)($id);

            if (!$result['success']) {
                return new JsonResponse($result, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}

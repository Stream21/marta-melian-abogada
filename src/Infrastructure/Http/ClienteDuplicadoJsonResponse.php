<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Exception\ClienteDuplicadoExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ClienteDuplicadoJsonResponse
{
    public static function create(ClienteDuplicadoExceptionInterface $exception): JsonResponse
    {
        return new JsonResponse([
            'message' => $exception->getMessage(),
            'clienteExistenteId' => $exception->clienteExistenteId(),
            'clienteExistenteNombre' => $exception->clienteExistenteNombre(),
        ], Response::HTTP_CONFLICT);
    }
}

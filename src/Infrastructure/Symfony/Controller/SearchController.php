<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\BusquedaGlobalUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/search', name: 'api_search_')]
final class SearchController extends AbstractController
{
    public function __construct(
        private BusquedaGlobalUseCase $busquedaGlobal,
    ) {
    }

    #[Route(path: '', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $query = (string) ($request->query->get('q') ?? '');
        $limit = (int) ($request->query->get('limit') ?? 20);

        $typesRaw = $request->query->get('types');
        $types = null;
        if (is_string($typesRaw) && '' !== trim($typesRaw)) {
            $types = array_values(array_filter(array_map(
                static fn (string $part): string => trim($part),
                explode(',', $typesRaw),
            )));
        }

        return new JsonResponse(($this->busquedaGlobal)($query, $types, $limit));
    }
}

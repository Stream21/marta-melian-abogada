<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Application\DTO\SearchHit;
use App\Application\Port\SearchProviderInterface;
use App\Domain\Entity\Expediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;

final class ExpedienteSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
    ) {
    }

    public function type(): string
    {
        return 'expediente';
    }

    public function search(string $query, int $limit): array
    {
        $expedientes = $this->expedienteRepository->search($query, $limit);
        $needle = mb_strtolower(trim($query));

        return array_map(
            function (Expediente $expediente) use ($needle): SearchHit {
                $id = $expediente->id()->value();
                $numero = $expediente->numero();
                $titulo = $expediente->titulo();
                $cliente = $expediente->clientName();
                $fase = $expediente->faseNegocio()->label();

                $subtitleParts = array_filter([
                    '' !== $cliente ? $cliente : null,
                    $fase,
                ]);

                return new SearchHit(
                    type: $this->type(),
                    id: $id,
                    title: $numero . ('' !== $titulo ? ' — ' . $titulo : ''),
                    subtitle: [] !== $subtitleParts ? implode(' · ', $subtitleParts) : 'Expediente',
                    href: '/expedientes/' . $id,
                    score: $this->score($needle, $numero, $titulo, $cliente),
                );
            },
            $expedientes,
        );
    }

    private function score(string $needle, string ...$fields): float
    {
        $best = 0.4;
        foreach ($fields as $field) {
            $value = mb_strtolower(trim($field));
            if ('' === $value || '' === $needle) {
                continue;
            }
            if ($value === $needle) {
                return 1.0;
            }
            if (str_starts_with($value, $needle)) {
                $best = max($best, 0.9);
            } elseif (str_contains($value, $needle)) {
                $best = max($best, 0.7);
            }
        }

        return $best;
    }
}

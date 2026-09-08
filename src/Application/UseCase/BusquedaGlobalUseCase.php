<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\SearchHit;
use App\Application\Port\SearchProviderInterface;

final class BusquedaGlobalUseCase
{
    private const MIN_QUERY_LENGTH = 2;
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 50;

    /**
     * @param iterable<SearchProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
    ) {
    }

    /**
     * @param list<string>|null $types
     *
     * @return array{query: string, hits: list<array<string, mixed>>}
     */
    public function __invoke(string $query, ?array $types = null, int $limit = self::DEFAULT_LIMIT): array
    {
        $trimmed = trim($query);
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        if (mb_strlen($trimmed) < self::MIN_QUERY_LENGTH) {
            return ['query' => $trimmed, 'hits' => []];
        }

        $allowed = null;
        if (null !== $types && [] !== $types) {
            $allowed = [];
            foreach ($types as $type) {
                $normalized = strtolower(trim((string) $type));
                if ('' !== $normalized) {
                    $allowed[$normalized] = true;
                }
            }
            if ([] === $allowed) {
                $allowed = null;
            }
        }

        $activeProviders = [];
        foreach ($this->providers as $provider) {
            if (!$provider instanceof SearchProviderInterface) {
                continue;
            }
            if (null !== $allowed && !isset($allowed[$provider->type()])) {
                continue;
            }
            $activeProviders[] = $provider;
        }

        $providerCount = max(1, count($activeProviders));
        $perProvider = max(5, (int) ceil($limit / $providerCount) + 2);

        /** @var list<SearchHit> $hits */
        $hits = [];
        foreach ($activeProviders as $provider) {
            foreach ($provider->search($trimmed, $perProvider) as $hit) {
                $hits[] = $hit;
            }
        }

        usort(
            $hits,
            static function (SearchHit $a, SearchHit $b): int {
                $scoreCmp = $b->score <=> $a->score;
                if (0 !== $scoreCmp) {
                    return $scoreCmp;
                }
                $typeCmp = $a->type <=> $b->type;
                if (0 !== $typeCmp) {
                    return $typeCmp;
                }

                return $a->title <=> $b->title;
            },
        );

        $sliced = array_slice($hits, 0, $limit);

        return [
            'query' => $trimmed,
            'hits' => array_map(static fn (SearchHit $hit): array => $hit->toArray(), $sliced),
        ];
    }
}

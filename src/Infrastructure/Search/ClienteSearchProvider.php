<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Application\DTO\SearchHit;
use App\Application\Port\SearchProviderInterface;
use App\Domain\Entity\Cliente;
use App\Domain\Repository\ClienteRepositoryInterface;

final class ClienteSearchProvider implements SearchProviderInterface
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
    ) {
    }

    public function type(): string
    {
        return 'cliente';
    }

    public function search(string $query, int $limit): array
    {
        $clientes = array_values(array_filter(
            $this->clienteRepository->search($query, $limit),
            static fn (Cliente $cliente) => !$cliente->esProvisional(),
        ));
        $needle = mb_strtolower(trim($query));

        return array_map(
            function (Cliente $cliente) use ($needle): SearchHit {
                $id = $cliente->id()->value();
                $nombre = $cliente->nombre();
                $doc = trim($cliente->tipoDocumento() . ' ' . $cliente->numDocumento());
                $parts = array_filter([
                    '' !== $doc ? $doc : null,
                    '' !== $cliente->telefono() ? $cliente->telefono() : null,
                    '' !== $cliente->email() ? $cliente->email() : null,
                ]);

                return new SearchHit(
                    type: $this->type(),
                    id: $id,
                    title: '' !== $nombre ? $nombre : 'Cliente sin nombre',
                    subtitle: [] !== $parts ? implode(' · ', $parts) : 'Ficha de cliente',
                    href: '/clientes/' . $id,
                    score: $this->score($needle, $nombre, $cliente->numDocumento(), $cliente->telefono(), $cliente->email()),
                );
            },
            $clientes,
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
                $best = max($best, 0.85);
            } elseif (str_contains($value, $needle)) {
                $best = max($best, 0.65);
            }
        }

        return $best;
    }
}

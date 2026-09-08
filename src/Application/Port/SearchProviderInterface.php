<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\DTO\SearchHit;

interface SearchProviderInterface
{
    /** Identificador estable del tipo de resultado (cliente, expediente, …). */
    public function type(): string;

    /**
     * @return list<SearchHit>
     */
    public function search(string $query, int $limit): array;
}

<?php

declare(strict_types=1);

namespace App\Application\DTO;

/**
 * Resultado uniforme del buscador global (extensible por type).
 */
final readonly class SearchHit
{
    public function __construct(
        public string $type,
        public string $id,
        public string $title,
        public string $subtitle,
        public string $href,
        public float $score = 0.0,
    ) {
    }

    /**
     * @return array{
     *     type: string,
     *     id: string,
     *     title: string,
     *     subtitle: string,
     *     href: string,
     *     score: float
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'href' => $this->href,
            'score' => $this->score,
        ];
    }
}

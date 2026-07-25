<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Paso de gestiones adicionales tras una resolución (TIE, tasas, modelos…).
 *
 * @phpstan-type GestionArray array{
 *   id: string,
 *   titulo: string,
 *   descripcion: string,
 *   url: ?string,
 *   hecho: bool,
 *   orden: int
 * }
 */
final readonly class GestionPostResolucion
{
    public function __construct(
        private string $id,
        private string $titulo,
        private string $descripcion,
        private ?string $url,
        private bool $hecho,
        private int $orden,
    ) {
        if ('' === trim($id) || '' === trim($titulo)) {
            throw new \InvalidArgumentException('Id y título de la gestión son obligatorios.');
        }
    }

    /**
     * @param GestionArray $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['titulo'] ?? ''),
            (string) ($data['descripcion'] ?? ''),
            isset($data['url']) && '' !== (string) $data['url'] ? (string) $data['url'] : null,
            (bool) ($data['hecho'] ?? false),
            (int) ($data['orden'] ?? 0),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function hecho(): bool
    {
        return $this->hecho;
    }

    public function orden(): int
    {
        return $this->orden;
    }

    public function withHecho(bool $hecho): self
    {
        return new self($this->id, $this->titulo, $this->descripcion, $this->url, $hecho, $this->orden);
    }

    /**
     * @return GestionArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'url' => $this->url,
            'hecho' => $this->hecho,
            'orden' => $this->orden,
        ];
    }
}

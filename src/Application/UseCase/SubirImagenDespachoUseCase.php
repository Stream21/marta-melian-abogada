<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\DespachoFileStoragePort;
use App\Domain\Entity\DespachoConfig;
use App\Domain\Repository\DespachoConfigRepositoryInterface;

final class SubirImagenDespachoUseCase
{
    private const ALLOWED_MIME = [
        'logo' => ['image/png', 'image/jpeg', 'image/webp', 'image/gif'],
        'sello' => ['image/png'],
    ];

    private const EXTENSION_MAP = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(
        private DespachoConfigRepositoryInterface $repository,
        private DespachoFileStoragePort $storage,
        private ObtenerDespachoConfigUseCase $obtener,
    ) {
    }

    public function __invoke(string $tipo, string $content, string $mimeType): DespachoConfig
    {
        if (!isset(self::ALLOWED_MIME[$tipo])) {
            throw new \InvalidArgumentException('Tipo de imagen no válido.');
        }

        if (!in_array($mimeType, self::ALLOWED_MIME[$tipo], true)) {
            $message = 'sello' === $tipo
                ? 'La firma debe guardarse en PNG con fondo transparente.'
                : 'Formato de imagen no permitido.';

            throw new \InvalidArgumentException($message);
        }

        $extension = self::EXTENSION_MAP[$mimeType] ?? 'png';
        $current = ($this->obtener)();

        if ('logo' === $tipo) {
            $path = $this->storage->saveLogo($content, $extension);
            $updated = $current->withLogoPath($path);
        } else {
            $path = $this->storage->saveSello($content, $extension);
            $updated = $current->withSelloPath($path);
        }

        $this->repository->save($updated);

        return ($this->obtener)();
    }
}

<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\GuardarHojaEncargoPlantillaInput;
use App\Domain\Entity\HojaEncargoPlantilla;
use App\Domain\Repository\HojaEncargoPlantillaRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\HojaEncargoPlantillaId;
use App\Domain\ValueObject\TramiteId;

final class GuardarHojaEncargoPlantillaUseCase
{
    private const ALLOWED_TYPES = [
        'header',
        'text',
        'section',
        'signature_client',
        'signature_lawyer',
        'footer',
    ];

    public function __construct(
        private HojaEncargoPlantillaRepositoryInterface $plantillaRepository,
        private TramiteRepositoryInterface $tramiteRepository,
    ) {
    }

    /**
     * @return array{tramiteId: string, esDefault: bool, bloques: list<array<string, mixed>>}
     */
    public function __invoke(GuardarHojaEncargoPlantillaInput $input): array
    {
        $tramiteId = new TramiteId($input->tramiteId);
        $tramite = $this->tramiteRepository->findById($tramiteId);
        if (null === $tramite) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $bloques = $this->validateBloques($input->bloques);

        $existing = $this->plantillaRepository->findByTramiteId($tramiteId);
        if (null === $existing) {
            $plantilla = new HojaEncargoPlantilla(
                HojaEncargoPlantillaId::generate(),
                $tramiteId,
                $bloques,
            );
        } else {
            $plantilla = $existing->withBloques($bloques);
        }

        $this->plantillaRepository->save($plantilla);

        return [
            'tramiteId' => $input->tramiteId,
            'esDefault' => false,
            'bloques' => $bloques,
        ];
    }

    /**
     * @param list<array<string, mixed>> $bloques
     *
     * @return list<array<string, mixed>>
     */
    private function validateBloques(array $bloques): array
    {
        if ([] === $bloques) {
            throw new \InvalidArgumentException('La plantilla debe contener al menos un bloque.');
        }

        $validated = [];
        foreach ($bloques as $index => $bloque) {
            if (!is_array($bloque)) {
                throw new \InvalidArgumentException(sprintf('Bloque %d no válido.', $index));
            }

            $type = $bloque['type'] ?? null;
            if (!is_string($type) || !in_array($type, self::ALLOWED_TYPES, true)) {
                throw new \InvalidArgumentException(sprintf('Tipo de bloque no válido en posición %d.', $index));
            }

            $id = $bloque['id'] ?? null;
            if (!is_string($id) || '' === trim($id)) {
                throw new \InvalidArgumentException(sprintf('El bloque %d debe tener un identificador.', $index));
            }

            $validated[] = match ($type) {
                'header' => [
                    'id' => $id,
                    'type' => 'header',
                    'title' => $this->requireString($bloque, 'title', $index),
                    'showLogo' => (bool) ($bloque['showLogo'] ?? true),
                    'showReferencia' => (bool) ($bloque['showReferencia'] ?? true),
                ],
                'text' => [
                    'id' => $id,
                    'type' => 'text',
                    'content' => $this->requireString($bloque, 'content', $index, allowEmpty: true),
                ],
                'section' => [
                    'id' => $id,
                    'type' => 'section',
                    'title' => $this->requireString($bloque, 'title', $index),
                    'content' => $this->requireString($bloque, 'content', $index, allowEmpty: true),
                ],
                'signature_client', 'signature_lawyer' => [
                    'id' => $id,
                    'type' => $type,
                    'label' => $this->requireString($bloque, 'label', $index),
                ],
                'footer' => [
                    'id' => $id,
                    'type' => 'footer',
                    'showPagination' => (bool) ($bloque['showPagination'] ?? true),
                ],
                default => throw new \InvalidArgumentException('Tipo de bloque no soportado.'),
            };
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $bloque
     */
    private function requireString(array $bloque, string $key, int $index, bool $allowEmpty = false): string
    {
        $value = $bloque[$key] ?? null;
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Campo "%s" inválido en bloque %d.', $key, $index));
        }

        if (!$allowEmpty && '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('Campo "%s" obligatorio en bloque %d.', $key, $index));
        }

        return $value;
    }
}

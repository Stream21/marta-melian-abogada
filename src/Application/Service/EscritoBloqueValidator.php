<?php

declare(strict_types=1);

namespace App\Application\Service;

use Ramsey\Uuid\Uuid;

final class EscritoBloqueValidator
{
    private const ALLOWED_TYPES = [
        'title',
        'text',
        'section',
        'table',
        'columns',
        'signature_client',
        'signature_lawyer',
    ];

    private const COLUMN_CHILD_TYPES = [
        'title',
        'text',
        'section',
        'signature_client',
        'signature_lawyer',
    ];

    /**
     * @param list<array<string, mixed>> $bloques
     *
     * @return list<array<string, mixed>>
     */
    public function normalizeLegacy(array $bloques): array
    {
        return $this->validateAndNormalize($bloques);
    }

    /**
     * @param list<array<string, mixed>> $bloques
     *
     * @return list<array<string, mixed>>
     */
    public function validateAndNormalize(array $bloques): array
    {
        if ([] === $bloques) {
            throw new \InvalidArgumentException('La plantilla debe contener al menos un bloque.');
        }

        $bloques = $this->migrateTopLevelSignaturesToColumns($bloques);
        $validated = [];
        foreach ($bloques as $index => $bloque) {
            if (!is_array($bloque)) {
                throw new \InvalidArgumentException(sprintf('Bloque %d no válido.', $index));
            }

            $type = $bloque['type'] ?? null;
            if ('header' === $type) {
                $type = 'title';
                $bloque['type'] = 'title';
                $bloque['title'] = $bloque['title'] ?? 'TÍTULO DEL DOCUMENTO';
                $bloque['showReferencia'] = $bloque['showReferencia'] ?? true;
            }
            if ('footer' === $type) {
                continue;
            }

            if (!is_string($type) || !in_array($type, self::ALLOWED_TYPES, true)) {
                throw new \InvalidArgumentException(sprintf('Tipo de bloque no válido en posición %d.', $index));
            }

            if (in_array($type, self::COLUMN_CHILD_TYPES, true)) {
                throw new \InvalidArgumentException(sprintf('Las firmas deben ir dentro de un bloque en columnas (posición %d).', $index));
            }

            $id = $bloque['id'] ?? null;
            if (!is_string($id) || '' === trim($id)) {
                throw new \InvalidArgumentException(sprintf('El bloque %d debe tener un identificador.', $index));
            }

            $validated[] = match ($type) {
                'title' => array_merge([
                    'id' => $id,
                    'type' => 'title',
                    'title' => $this->requireString($bloque, 'title', $index),
                    'showReferencia' => (bool) ($bloque['showReferencia'] ?? true),
                ], $this->optionalStyle($bloque)),
                'text' => array_merge([
                    'id' => $id,
                    'type' => 'text',
                    'content' => $this->requireString($bloque, 'content', $index, allowEmpty: true),
                ], $this->optionalStyle($bloque)),
                'section' => array_merge([
                    'id' => $id,
                    'type' => 'section',
                    'title' => $this->requireString($bloque, 'title', $index),
                    'content' => $this->requireString($bloque, 'content', $index, allowEmpty: true),
                ], $this->optionalStyle($bloque)),
                'table' => [
                    'id' => $id,
                    'type' => 'table',
                    'clauseTitle' => $this->requireString($bloque, 'clauseTitle', $index),
                    'title' => $this->requireString($bloque, 'title', $index),
                    'subtitle' => $this->requireString($bloque, 'subtitle', $index, allowEmpty: true),
                    'rows' => $this->normalizeTableRows($bloque['rows'] ?? null, $index),
                ],
                'columns' => [
                    'id' => $id,
                    'type' => 'columns',
                    'columnCount' => $this->normalizeColumnCount($bloque['columnCount'] ?? null, $index),
                    'children' => $this->normalizeColumnChildren(
                        $bloque['children'] ?? null,
                        $this->normalizeColumnCount($bloque['columnCount'] ?? null, $index),
                        $index,
                    ),
                ],
                default => throw new \InvalidArgumentException('Tipo de bloque no soportado.'),
            };
        }

        if ([] === $validated) {
            throw new \InvalidArgumentException('La plantilla debe contener al menos un bloque.');
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

    /**
     * @return list<array{label: string, value: string}>
     */
    private function normalizeTableRows(mixed $rows, int $index): array
    {
        if (!is_array($rows) || [] === $rows) {
            throw new \InvalidArgumentException(sprintf('El bloque tabla %d debe incluir filas.', $index));
        }

        $normalized = [];
        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException(sprintf('Fila %d inválida en bloque tabla %d.', $rowIndex, $index));
            }

            $normalized[] = [
                'label' => $this->requireTableCell($row, 'label', $index, $rowIndex),
                'value' => $this->requireTableCell($row, 'value', $index, $rowIndex, allowEmpty: true),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function requireTableCell(
        array $row,
        string $key,
        int $blockIndex,
        int $rowIndex,
        bool $allowEmpty = false,
    ): string {
        $value = $row[$key] ?? null;
        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Campo "%s" inválido en fila %d del bloque tabla %d.', $key, $rowIndex, $blockIndex));
        }

        if (!$allowEmpty && '' === trim($value)) {
            throw new \InvalidArgumentException(sprintf('Campo "%s" obligatorio en fila %d del bloque tabla %d.', $key, $rowIndex, $blockIndex));
        }

        return $value;
    }

    /**
     * @param list<array<string, mixed>> $bloques
     *
     * @return list<array<string, mixed>>
     */
    private function migrateTopLevelSignaturesToColumns(array $bloques): array
    {
        $migrated = [];
        $count = count($bloques);

        for ($i = 0; $i < $count; ++$i) {
            $current = $bloques[$i];
            $type = (string) ($current['type'] ?? '');
            $next = $bloques[$i + 1] ?? null;
            $nextType = is_array($next) ? (string) ($next['type'] ?? '') : '';

            if ('columns' === $type) {
                $migrated[] = $current;
                continue;
            }

            if (!in_array($type, self::COLUMN_CHILD_TYPES, true)) {
                $migrated[] = $current;
                continue;
            }

            if (
                is_array($next)
                && (
                    ('signature_lawyer' === $type && 'signature_client' === $nextType)
                    || ('signature_client' === $type && 'signature_lawyer' === $nextType)
                )
            ) {
                $migrated[] = [
                    'id' => Uuid::uuid4()->toString(),
                    'type' => 'columns',
                    'columnCount' => 2,
                    'children' => [$current, $next],
                ];
                ++$i;
                continue;
            }

            $migrated[] = [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'columns',
                'columnCount' => 1,
                'children' => [$current],
            ];
        }

        return $migrated;
    }

    private function normalizeColumnCount(mixed $columnCount, int $index): int
    {
        if (!is_int($columnCount) || !in_array($columnCount, [1, 2], true)) {
            throw new \InvalidArgumentException(sprintf('El bloque en columnas %d debe indicar 1 o 2 columnas.', $index));
        }

        return $columnCount;
    }

    /**
     * @return list<array<string, mixed>|null>
     */
    private function normalizeColumnChildren(mixed $children, int $columnCount, int $index): array
    {
        if (!is_array($children)) {
            $children = [];
        }

        $normalized = [];
        for ($slot = 0; $slot < $columnCount; ++$slot) {
            $child = $children[$slot] ?? null;
            if (null === $child) {
                $normalized[] = null;
                continue;
            }

            if (!is_array($child)) {
                throw new \InvalidArgumentException(sprintf('Componente %d inválido en bloque columnas %d.', $slot, $index));
            }

            $normalized[] = $this->normalizeColumnChild($child, $index, $slot);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $child
     *
     * @return array<string, mixed>
     */
    private function normalizeColumnChild(array $child, int $blockIndex, int $slotIndex): array
    {
        $type = (string) ($child['type'] ?? '');
        if (!in_array($type, self::COLUMN_CHILD_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Tipo de componente no permitido en columna %d del bloque %d.', $slotIndex, $blockIndex));
        }

        $childId = $child['id'] ?? null;
        if (!is_string($childId) || '' === trim($childId)) {
            throw new \InvalidArgumentException(sprintf('El componente %d del bloque columnas %d debe tener id.', $slotIndex, $blockIndex));
        }

        return match ($type) {
            'title' => array_merge([
                'id' => $childId,
                'type' => 'title',
                'title' => $this->requireString($child, 'title', $blockIndex, allowEmpty: true),
                'showReferencia' => (bool) ($child['showReferencia'] ?? false),
            ], $this->optionalStyle($child)),
            'text' => array_merge([
                'id' => $childId,
                'type' => 'text',
                'content' => $this->requireString($child, 'content', $blockIndex, allowEmpty: true),
            ], $this->optionalStyle($child)),
            'section' => array_merge([
                'id' => $childId,
                'type' => 'section',
                'title' => $this->requireString($child, 'title', $blockIndex, allowEmpty: true),
                'content' => $this->requireString($child, 'content', $blockIndex, allowEmpty: true),
            ], $this->optionalStyle($child)),
            'signature_client', 'signature_lawyer' => [
                'id' => $childId,
                'type' => $type,
                'label' => is_string($child['label'] ?? null) ? (string) $child['label'] : '',
            ],
            default => throw new \InvalidArgumentException('Tipo de componente no soportado.'),
        };
    }

    /**
     * @param array<string, mixed> $bloque
     *
     * @return array{style?: array{align: string, fontSize: int}}
     */
    private function optionalStyle(array $bloque): array
    {
        $style = $bloque['style'] ?? null;
        if (!is_array($style)) {
            return [];
        }

        $normalized = [];
        $align = $style['align'] ?? null;
        if (is_string($align) && in_array($align, ['left', 'center', 'right', 'justify'], true)) {
            $normalized['align'] = $align;
        }

        $fontSize = $style['fontSize'] ?? null;
        if (is_int($fontSize) && in_array($fontSize, [10, 11, 12, 14, 16, 18], true)) {
            $normalized['fontSize'] = $fontSize;
        }

        return [] === $normalized ? [] : ['style' => $normalized];
    }
}

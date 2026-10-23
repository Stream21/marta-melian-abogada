<?php

declare(strict_types=1);

namespace App\Tests\Application\Service;

use App\Application\Service\EscritoBloqueValidator;
use PHPUnit\Framework\TestCase;

final class EscritoBloqueValidatorTest extends TestCase
{
    public function testKeepsTitleTextSectionAtRootAndOnlyWrapsSignatures(): void
    {
        $validator = new EscritoBloqueValidator();

        $normalized = $validator->validateAndNormalize([
            ['id' => 't1', 'type' => 'title', 'title' => 'HOJA DE ENCARGO', 'showReferencia' => true],
            ['id' => 's1', 'type' => 'section', 'title' => 'REUNIDOS', 'content' => 'Parte A'],
            ['id' => 's2', 'type' => 'section', 'title' => 'EXPONEN', 'content' => 'Parte B'],
            ['id' => 's3', 'type' => 'section', 'title' => 'ESTIPULACIONES', 'content' => str_repeat('Cláusula. ', 80)],
            ['id' => 'sigL', 'type' => 'signature_lawyer', 'label' => ''],
            ['id' => 'sigC', 'type' => 'signature_client', 'label' => ''],
        ]);

        self::assertSame('title', $normalized[0]['type']);
        self::assertSame('section', $normalized[1]['type']);
        self::assertSame('section', $normalized[2]['type']);
        self::assertSame('section', $normalized[3]['type']);
        self::assertSame('ESTIPULACIONES', $normalized[3]['title']);
        self::assertSame('columns', $normalized[4]['type']);
        self::assertSame(2, $normalized[4]['columnCount']);
        self::assertSame('signature_lawyer', $normalized[4]['children'][0]['type']);
        self::assertSame('signature_client', $normalized[4]['children'][1]['type']);
        self::assertCount(5, $normalized);
    }

    public function testUnwrapsLegacySingleColumnTextWrappers(): void
    {
        $validator = new EscritoBloqueValidator();

        $normalized = $validator->validateAndNormalize([
            [
                'id' => 'c1',
                'type' => 'columns',
                'columnCount' => 1,
                'children' => [
                    ['id' => 's1', 'type' => 'section', 'title' => 'EXPONEN', 'content' => 'Texto corto'],
                ],
            ],
            [
                'id' => 'c2',
                'type' => 'columns',
                'columnCount' => 1,
                'children' => [
                    ['id' => 'sig', 'type' => 'signature_lawyer', 'label' => ''],
                ],
            ],
        ]);

        self::assertSame('section', $normalized[0]['type']);
        self::assertSame('EXPONEN', $normalized[0]['title']);
        self::assertSame('columns', $normalized[1]['type']);
        self::assertSame(1, $normalized[1]['columnCount']);
        self::assertSame('signature_lawyer', $normalized[1]['children'][0]['type']);
    }
}

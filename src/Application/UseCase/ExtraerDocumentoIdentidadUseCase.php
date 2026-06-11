<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\DocumentoIdentidadExtractorPort;
use App\Domain\Entity\TipoEscaneoDocumentoIdentidad;

final class ExtraerDocumentoIdentidadUseCase
{
    public function __construct(
        private DocumentoIdentidadExtractorPort $extractor,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $tipoEscaneo, string $anversoBinary, ?string $reversoBinary): array
    {
        $tipo = TipoEscaneoDocumentoIdentidad::tryFrom($tipoEscaneo)
            ?? throw new \InvalidArgumentException('Tipo de escaneo no válido. Use dni_nie o pasaporte.');

        if ('' === $anversoBinary) {
            throw new \InvalidArgumentException('La imagen del anverso es obligatoria.');
        }

        if ($tipo->requiereReverso() && (null === $reversoBinary || '' === $reversoBinary)) {
            throw new \InvalidArgumentException('La imagen del reverso es obligatoria para DNI/NIE.');
        }

        $anversoPath = $this->writeTemp($anversoBinary, 'anverso');
        $reversoPath = null !== $reversoBinary && '' !== $reversoBinary
            ? $this->writeTemp($reversoBinary, 'reverso')
            : null;

        try {
            $datos = $this->extractor->extract($tipo->value, $anversoPath, $reversoPath);
        } finally {
            @unlink($anversoPath);
            if (null !== $reversoPath) {
                @unlink($reversoPath);
            }
        }

        return [
            'tipoEscaneo' => $tipo->value,
            'tipoEscaneoLabel' => $tipo->label(),
            'datosExtraidos' => $datos,
        ];
    }

    private function writeTemp(string $content, string $suffix): string
    {
        $path = sys_get_temp_dir() . '/doc-id-' . bin2hex(random_bytes(8)) . '-' . $suffix . '.jpg';
        file_put_contents($path, $content);

        return $path;
    }
}

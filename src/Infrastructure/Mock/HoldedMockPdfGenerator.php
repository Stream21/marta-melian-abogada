<?php

declare(strict_types=1);

namespace App\Infrastructure\Mock;

/**
 * Genera PDFs mínimos pero válidos para simular facturas de Holded en desarrollo.
 */
final class HoldedMockPdfGenerator
{
    public function generate(
        string $number,
        string $clientName,
        float $total,
        ?string $reference = null,
        ?string $concept = null,
    ): string {
        $lines = [
            'FACTURA (SIMULACION HOLDED)',
            'Numero: ' . $number,
            'Cliente: ' . $clientName,
        ];

        if (null !== $reference && '' !== $reference) {
            $lines[] = 'Referencia: ' . $reference;
        }

        if (null !== $concept && '' !== $concept) {
            $lines[] = 'Concepto: ' . $concept;
        }

        $lines[] = 'Importe: ' . number_format($total, 2, '.', '') . ' EUR';
        $lines[] = 'Fecha: ' . (new \DateTimeImmutable())->format('d/m/Y');

        return $this->buildPdf($lines);
    }

    /**
     * @param list<string> $lines
     */
    private function buildPdf(array $lines): string
    {
        $stream = "BT\n";
        $stream .= "/F1 12 Tf\n";
        $stream .= "50 780 Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "0 -22 Td\n";
            }
            $stream .= '(' . $this->escapePdfString($line) . ") Tj\n";
        }

        $stream .= "ET\n";

        $streamLen = strlen($stream);
        $objects = [];
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $objects[4] = "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}endstream\nendobj\n";
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";

        $header = "%PDF-1.4\n";
        $body = '';
        $offsets = [];

        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($header) + strlen($body);
            $body .= $obj;
        }

        $xrefOffset = strlen($header) + strlen($body);
        $xref = "xref\n";
        $xref .= '0 ' . (count($objects) + 1) . "\n";
        $xref .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $xref .= str_pad((string) $offset, 10, '0', \STR_PAD_LEFT) . " 00000 n \n";
        }

        $trailer = "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $header . $body . $xref . $trailer;
    }

    private function escapePdfString(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}

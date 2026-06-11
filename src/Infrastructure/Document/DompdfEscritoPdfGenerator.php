<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

use App\Application\Port\EscritoPdfGeneratorPort;
use Dompdf\Dompdf;
use Dompdf\Options;

final class DompdfEscritoPdfGenerator implements EscritoPdfGeneratorPort
{
    public function generateFromHtml(string $html): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'dejavu sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}

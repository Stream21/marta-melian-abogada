<?php

declare(strict_types=1);

namespace App\Application\Port;

interface EscritoPdfGeneratorPort
{
    public function generateFromHtml(string $html): string;
}

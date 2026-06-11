<?php

declare(strict_types=1);

namespace App\Application\Port;

interface DespachoFileStoragePort
{
    public function saveLogo(string $content, string $extension): string;

    public function saveSello(string $content, string $extension): string;

    public function getAbsolutePath(string $relativePath): string;
}

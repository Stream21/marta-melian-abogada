<?php

declare(strict_types=1);

$path = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (pathinfo($path, PATHINFO_EXTENSION) !== '' && is_file($path)) {
    return false;
}
require_once __DIR__ . '/index.php';

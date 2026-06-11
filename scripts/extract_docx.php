<?php

declare(strict_types=1);

$path = $argv[1] ?? '';
if ('' === $path || !is_readable($path)) {
    fwrite(STDERR, "Usage: php extract_docx.php <path>\n");
    exit(1);
}

$zip = new ZipArchive();
if (true !== $zip->open($path)) {
    fwrite(STDERR, "Cannot open docx\n");
    exit(1);
}

$xml = $zip->getFromName('word/document.xml');
$zip->close();

if (false === $xml) {
    fwrite(STDERR, "No document.xml\n");
    exit(1);
}

$text = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml);
$text = preg_replace('/<\/w:p>/', "\n", (string) $text);
$text = strip_tags((string) $text);
$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

foreach (explode("\n", $text) as $line) {
    $line = trim($line);
    if ('' !== $line) {
        echo $line, "\n";
    }
}

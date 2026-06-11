<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\DespachoConfig;

final class EscritoHtmlRenderer
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $bloques
     * @param array<string, string>    $variables
     */
    public function render(
        array $bloques,
        array $variables,
        ?DespachoConfig $despacho,
        bool $incluirMembrete,
    ): string {
        $logoUri = $this->assetDataUri($despacho?->logoPath());
        $selloUri = $this->assetDataUri($despacho?->selloPath());
        $cabecera = $incluirMembrete ? $this->renderMembrete('cabecera', $despacho, $variables, $logoUri) : '';
        $pie = $incluirMembrete ? $this->renderMembrete('pie', $despacho, $variables, $logoUri) : '';
        $body = $this->renderBody($bloques, $variables, $selloUri, $logoUri);

        $pageMarginTop = $incluirMembrete ? '118px' : '48px';
        $pageMarginBottom = $incluirMembrete ? '128px' : '48px';
        $pageMarginSides = '52px';
        $headerBlock = $incluirMembrete ? '<header>' . $cabecera . '</header>' : '';
        $footerBlock = $incluirMembrete ? '<footer>' . $pie . '</footer>' : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page { margin: {$pageMarginTop} {$pageMarginSides} {$pageMarginBottom} {$pageMarginSides}; }
* { box-sizing: border-box; }
body {
  font-family: 'DejaVu Sans', sans-serif;
  font-size: 10.5pt;
  line-height: 1.65;
  color: #1f2937;
  margin: 0;
}
header { position: fixed; top: -102px; left: 0; right: 0; height: 92px; overflow: hidden; }
footer { position: fixed; bottom: -112px; left: 0; right: 0; height: 104px; overflow: hidden; }
main { position: relative; z-index: 1; }
.membrete-nombre { font-size: 11pt; font-weight: bold; color: #2c3e6b; line-height: 1.2; }
.membrete-subtitulo { font-size: 9pt; font-style: italic; color: #5c6b82; margin-top: 2px; line-height: 1.2; }
.membrete-rule { border-bottom: 1.5px solid #2c3e6b; margin-top: 4px; }
.block { margin-bottom: 8px; }
.block-title { margin-bottom: 12px; }
.title {
  font-weight: bold;
  text-align: center;
  color: #2c3e6b;
  margin: 0 0 4px;
  font-size: 16pt;
  letter-spacing: 0.04em;
  line-height: 1.25;
}
.section-heading {
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  margin: 14px 0 8px;
  font-size: 12pt;
  color: #2c3e6b;
  letter-spacing: 0.06em;
}
.clause-subtitle {
  font-weight: bold;
  text-align: left;
  margin: 10px 0 4px;
  font-size: 10.5pt;
  color: #1f2937;
}
.paragraph { margin: 0 0 6px; text-align: justify; }
.date-line { margin: 0 0 10px; text-align: center; font-size: 10.5pt; }
.ref {
  font-size: 8pt;
  color: #6b7280;
  margin: 0 0 8px;
  text-align: center;
  letter-spacing: 0.04em;
}
.legal-table-wrap { margin: 6px 0 10px; page-break-inside: avoid; }
.legal-table-title { font-weight: bold; text-align: center; margin: 0 0 4px; font-size: 10pt; color: #2c3e6b; }
.legal-table-subtitle { text-align: center; font-size: 9pt; margin: 0 0 8px; color: #5c6b82; }
.legal-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
.legal-table td { border: 1px solid #c5d0de; padding: 5px 7px; vertical-align: top; text-align: left; }
.legal-table-label { width: 26%; font-weight: bold; background: #f0f4f8; color: #2c3e6b; }
.legal-table-value { background: #ffffff; }
.signature-row {
  display: table;
  width: 100%;
  margin-top: 28px;
  page-break-inside: avoid;
}
.signature-box,
.column-cell {
  display: table-cell;
  width: 50%;
  padding: 0 10px;
  vertical-align: top;
}
.column-slot-content .block {
  margin-bottom: 4px;
}
.column-slot-content .title {
  font-size: 12pt;
}
.column-slot-content .section-heading {
  font-size: 10pt;
  margin-top: 6px;
}
.signature-row-single .column-cell {
  display: block;
  width: 100%;
}
.signature-area {
  position: relative;
  height: 95px;
  border: 1px solid #c5d0de;
  background: #f8fafc;
  border-radius: 6px;
  margin-bottom: 8px;
  text-align: center;
  overflow: hidden;
}
.signature-area-watermark {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  text-align: center;
  opacity: 0.1;
  z-index: 0;
}
.signature-area-watermark img {
  margin-top: 18px;
  max-height: 52px;
  max-width: 68px;
}
.signature-area-content {
  position: relative;
  z-index: 1;
  height: 100%;
  padding: 8px;
}
.signature-area .stamp {
  max-height: 72px;
  max-width: 110px;
  margin: 8px auto 0;
  display: block;
}
.signature-placeholder {
  color: #9ca3af;
  font-size: 8pt;
  margin-top: 34px;
  font-style: italic;
}
.signature-name {
  font-size: 9pt;
  font-weight: bold;
  text-transform: uppercase;
  text-align: center;
  color: #1f2937;
  margin-top: 6px;
  line-height: 1.2;
}
.signature-role {
  font-size: 8pt;
  text-align: center;
  color: #6b7280;
  margin-top: 2px;
}
.signature-caption {
  font-size: 8.5pt;
  font-weight: bold;
  text-transform: uppercase;
  text-align: center;
  color: #2c3e6b;
  letter-spacing: 0.05em;
  margin-top: 4px;
}
.signature-single { margin-top: 28px; page-break-inside: avoid; }
strong { font-weight: bold; }
</style>
</head>
<body>
{$headerBlock}
{$footerBlock}
<main>{$body}</main>
</body>
</html>
HTML;
    }

    /**
     * @param list<array<string, mixed>> $bloques
     */
    private function renderBody(array $bloques, array $variables, ?string $selloUri, ?string $logoUri): string
    {
        $body = '';
        $count = count($bloques);

        for ($i = 0; $i < $count; ++$i) {
            $bloque = $bloques[$i];
            $type = (string) ($bloque['type'] ?? '');

            if ('columns' === $type) {
                $body .= $this->renderColumns($bloque, $variables, $selloUri, $logoUri);
                continue;
            }

            $body .= $this->renderBloque($bloque, $variables, $selloUri);
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $bloque
     */
    private function renderBloque(array $bloque, array $variables, ?string $selloUri): string
    {
        $type = (string) ($bloque['type'] ?? '');
        $style = is_array($bloque['style'] ?? null) ? $bloque['style'] : [];
        $styleAttr = $this->blockStyleAttr($style);

        return match ($type) {
            'title' => $this->renderTitle($bloque, $variables, $styleAttr),
            'text' => '<div class="block">' . $this->renderContent((string) ($bloque['content'] ?? ''), $variables, $styleAttr) . '</div>',
            'section' => $this->renderSection($bloque, $variables, $styleAttr),
            'table' => $this->renderTable($bloque, $variables),
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $bloque
     */
    private function renderTitle(array $bloque, array $variables, string $styleAttr): string
    {
        $align = $this->titleAlign($bloque['style'] ?? null);
        $title = $this->renderInline((string) ($bloque['title'] ?? ''), $variables);
        $ref = !empty($bloque['showReferencia'])
            ? '<p class="ref">REFERENCIA: ' . htmlspecialchars($variables['REFERENCIA_EXPEDIENTE'] ?? '', ENT_QUOTES, 'UTF-8') . '</p>'
            : '';

        return '<div class="block block-title" style="text-align:' . $align . '"><h1 class="title"' . $styleAttr . '>' . $title . '</h1>' . $ref . '</div>';
    }

    /**
     * @param array<string, mixed> $bloque
     */
    private function renderSection(array $bloque, array $variables, string $styleAttr): string
    {
        $title = htmlspecialchars((string) ($bloque['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $content = $this->renderContent((string) ($bloque['content'] ?? ''), $variables, $styleAttr);

        return '<div class="block"><p class="section-heading">' . $title . '</p>' . $content . '</div>';
    }

    /**
     * @param array<string, mixed> $bloque
     */
    private function renderTable(array $bloque, array $variables): string
    {
        $clauseTitle = htmlspecialchars((string) ($bloque['clauseTitle'] ?? ''), ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars((string) ($bloque['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $subtitle = htmlspecialchars((string) ($bloque['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8');
        $rows = is_array($bloque['rows'] ?? null) ? $bloque['rows'] : [];

        $bodyRows = '';
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = htmlspecialchars((string) ($row['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $value = $this->renderInline((string) ($row['value'] ?? ''), $variables);
            $bodyRows .= '<tr><td class="legal-table-label">' . $label . '</td><td class="legal-table-value">' . $value . '</td></tr>';
        }

        $subtitleHtml = '' !== trim($subtitle)
            ? '<p class="legal-table-subtitle">' . $subtitle . '</p>'
            : '';

        return '<div class="block legal-table-wrap">'
            . '<p class="clause-subtitle">' . $clauseTitle . '</p>'
            . '<p class="legal-table-title">' . $title . '</p>'
            . $subtitleHtml
            . '<table class="legal-table"><tbody>' . $bodyRows . '</tbody></table>'
            . '</div>';
    }

    /**
     * @param array<string, mixed>  $bloque
     * @param array<string, string> $variables
     */
    private function renderColumns(array $bloque, array $variables, ?string $selloUri, ?string $logoUri): string
    {
        $columnCount = (int) ($bloque['columnCount'] ?? 2);
        if ($columnCount < 1) {
            $columnCount = 1;
        }

        $children = is_array($bloque['children'] ?? null) ? $bloque['children'] : [];
        $cells = '';

        for ($slot = 0; $slot < $columnCount; ++$slot) {
            $child = $children[$slot] ?? null;
            if (!is_array($child)) {
                $cells .= '<div class="column-cell"></div>';
                continue;
            }

            $cells .= '<div class="column-cell">' . $this->renderColumnSlot($child, $variables, $selloUri, $logoUri) . '</div>';
        }

        $rowClass = 1 === $columnCount ? 'signature-row signature-row-single' : 'signature-row';

        return '<div class="' . $rowClass . '">' . $cells . '</div>';
    }

    /**
     * @param array<string, mixed>  $bloque
     * @param array<string, string> $variables
     */
    private function renderColumnSlot(array $bloque, array $variables, ?string $selloUri, ?string $logoUri): string
    {
        $type = (string) ($bloque['type'] ?? '');
        if ('signature_client' === $type || 'signature_lawyer' === $type) {
            return $this->renderSignatureChild($bloque, $variables, $selloUri, $logoUri);
        }

        return '<div class="column-slot-content">' . $this->renderBloque($bloque, $variables, $selloUri) . '</div>';
    }

    /**
     * @param array<string, mixed>  $bloque
     * @param array<string, string> $variables
     */
    private function renderSignatureChild(array $bloque, array $variables, ?string $selloUri, ?string $logoUri): string
    {
        $type = (string) ($bloque['type'] ?? '');
        $isClient = 'signature_client' === $type;
        $slotLabel = $isClient ? 'POR EL CLIENTE' : 'POR LA ABOGADA';
        $inner = !$isClient && null !== $selloUri
            ? '<img class="stamp" src="' . htmlspecialchars($selloUri, ENT_QUOTES, 'UTF-8') . '" alt="" />'
            : '<div class="signature-placeholder">' . ($isClient ? 'Firma del cliente' : 'Firma de la letrada') . '</div>';
        $nameKeys = $isClient ? ['NOMBRE_CLIENTE'] : ['NOMBRE_LETRADA', 'NOMBRE_FIRMA'];

        return $this->renderSignatureArea($inner, $logoUri)
            . '<div class="signature-caption">' . htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8') . '</div>'
            . $this->renderSignatureNameFooter($variables, ...$nameKeys);
    }

    /**
     * @param array<string, string> $variables
     * @param string                ...$nameKeys
     */
    private function renderSignatureNameFooter(array $variables, string ...$nameKeys): string
    {
        $name = '';
        foreach ($nameKeys as $key) {
            $candidate = trim($variables[$key] ?? '');
            if ('' !== $candidate) {
                $name = $candidate;
                break;
            }
        }

        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        return '<div class="signature-name">' . $safeName . '</div>';
    }

    private function renderSignatureArea(string $innerHtml, ?string $logoUri): string
    {
        $watermark = null !== $logoUri
            ? '<div class="signature-area-watermark"><img src="' . htmlspecialchars($logoUri, ENT_QUOTES, 'UTF-8') . '" alt="" /></div>'
            : '';

        return '<div class="signature-area">' . $watermark . '<div class="signature-area-content">' . $innerHtml . '</div></div>';
    }

    /**
     * @param array<string, mixed>|null $style
     */
    private function blockStyleAttr(?array $style): string
    {
        if (null === $style) {
            return '';
        }

        $parts = [];
        $align = $style['align'] ?? null;
        if (is_string($align) && in_array($align, ['left', 'center', 'right', 'justify'], true)) {
            $parts[] = 'text-align:' . $align;
        }

        $fontSize = $style['fontSize'] ?? null;
        if (is_int($fontSize) && in_array($fontSize, [10, 11, 12, 14, 16, 18], true)) {
            $parts[] = 'font-size:' . $fontSize . 'pt';
        }

        return [] === $parts ? '' : ' style="' . implode(';', $parts) . '"';
    }

    /**
     * @param array<string, mixed>|null $style
     */
    private function titleAlign(mixed $style): string
    {
        if (!is_array($style)) {
            return 'center';
        }

        $align = $style['align'] ?? null;

        return is_string($align) && in_array($align, ['left', 'center', 'right', 'justify'], true)
            ? $align
            : 'center';
    }

    private function renderContent(string $text, array $variables, string $styleAttr): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        if (1 === count($lines)) {
            $class = str_contains($styleAttr, 'center') ? 'date-line' : 'paragraph';

            return '<p class="' . $class . '"' . $styleAttr . '>' . $this->renderInline($text, $variables) . '</p>';
        }

        $html = '';
        foreach ($lines as $line) {
            if ('' === trim($line)) {
                $html .= '<br />';
                continue;
            }

            $inline = $this->renderInline($line, $variables);
            if ($this->isClauseSubtitle($line)) {
                $html .= '<p class="clause-subtitle">' . $inline . '</p>';
            } else {
                $html .= '<p class="paragraph"' . $styleAttr . '>' . $inline . '</p>';
            }
        }

        return $html;
    }

    private function isClauseSubtitle(string $line): bool
    {
        return (bool) preg_match(
            '/^(PRIMERA|SEGUNDA|TERCERA|CUARTA|QUINTA|SEXTA|S[EÉ]PTIMA|OCTAVA|NOVENA|D[EÉ]CIMA|UND[EÉ]CIMA|DUOD[EÉ]CIMA)\s*\.?\s*-/iu',
            trim($line),
        );
    }

    private function renderInline(string $text, array $variables): string
    {
        $parts = preg_split('/(\[\[[A-Z_]+\]\])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $escaped = '';

        foreach ($parts as $part) {
            if ('' === $part) {
                continue;
            }

            if (preg_match('/^\[\[([A-Z_]+)\]\]$/', $part, $matches)) {
                $value = htmlspecialchars($variables[$matches[1]] ?? $part, ENT_QUOTES, 'UTF-8');
                $escaped .= '<strong>' . $value . '</strong>';
                continue;
            }

            $escaped .= htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
        }

        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/__(.+?)__/s', '<u>$1</u>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $escaped) ?? $escaped;

        return $escaped;
    }

    /**
     * @param array<string, string> $variables
     */
    private function renderMembrete(string $position, ?DespachoConfig $despacho, array $variables, ?string $logoUri): string
    {
        $customHtml = 'cabecera' === $position ? $despacho?->cabeceraHtml() : $despacho?->pieHtml();
        $html = null !== $customHtml && '' !== trim($customHtml)
            ? $customHtml
            : ('cabecera' === $position ? DespachoMembreteDefaults::cabeceraHtml() : DespachoMembreteDefaults::pieHtml());

        return $this->substituteMembrete($html, $variables, $logoUri);
    }

    /**
     * @param array<string, string> $variables
     */
    private function substituteMembrete(string $html, array $variables, ?string $logoUri): string
    {
        $logoTag = null !== $logoUri
            ? '<div style="display:inline-block;background:#2c3e6b;padding:6px 8px;border-radius:4px;line-height:0;">'
                . '<img src="' . htmlspecialchars($logoUri, ENT_QUOTES, 'UTF-8') . '" alt="" style="max-height:40px;max-width:56px;display:block;" />'
                . '</div>'
            : '';

        $html = str_replace('[[LOGO_DESPACHO]]', $logoTag, $html);

        return preg_replace_callback(
            '/\[\[([A-Z_]+)\]\]/',
            static fn (array $matches): string => htmlspecialchars($variables[$matches[1]] ?? $matches[0], ENT_QUOTES, 'UTF-8'),
            $html,
        ) ?? $html;
    }

    private function assetDataUri(?string $relativePath): ?string
    {
        if (null === $relativePath || '' === trim($relativePath)) {
            return null;
        }

        $path = str_starts_with($relativePath, 'var/despacho/')
            ? $this->projectDir . '/' . ltrim($relativePath, '/')
            : $this->projectDir . '/public/' . ltrim($relativePath, '/');

        if (!is_readable($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $content = file_get_contents($path);
        if (false === $content) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}

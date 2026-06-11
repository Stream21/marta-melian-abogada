<?php

declare(strict_types=1);

namespace App\Application\Service;

final class DespachoMembreteDefaults
{
    public const PIE_HEIGHT_PX = 112;

    public static function cabeceraHtml(): string
    {
        return <<<'HTML'
<table style="width:100%;border-collapse:collapse;font-family:'DejaVu Sans',sans-serif;">
<tr>
<td style="vertical-align:middle;text-align:left;padding-bottom:6px;">
<div class="membrete-nombre">[[NOMBRE_FIRMA]]</div>
<div class="membrete-subtitulo">[[SUBTITULO_PROFESIONAL]]</div>
</td>
<td style="width:72px;vertical-align:middle;text-align:right;padding-bottom:6px;">[[LOGO_DESPACHO]]</td>
</tr>
</table>
<div class="membrete-rule"></div>
HTML;
    }

    public static function pieHtml(): string
    {
        $height = self::PIE_HEIGHT_PX;
        $waveUri = self::svgDataUri(self::pieWaveSvg());
        $contactLines = self::pieContactLines(
            self::svgDataUri(self::iconSvg('M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z')),
            '[[DOMICILIO_DESPACHO]]',
            self::svgDataUri(self::iconSvg('M12 2a10 10 0 100 20 10 10 0 000-20zm0 18a8 8 0 010-16 8 8 0 010 16zm-1-13h2v6h-2V7zm0 8h2v2h-2v-2z')),
            '[[WEB_DESPACHO]]',
            self::svgDataUri(self::iconSvg('M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z')),
            '[[EMAIL_DESPACHO]]',
            self::svgDataUri(self::iconSvg('M6.62 10.79a15.05 15.05 0 006.59 6.59l2.2-2.2a1 1 0 011.01-.24c1.12.37 2.33.57 3.57.57a1 1 0 011 1V20a1 1 0 01-1 1C10.07 21 3 13.93 3 5a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1.01l-2.2 2.21z')),
            '[[TELEFONO_DESPACHO]]',
        );

        return <<<HTML
<div class="doc-pie" style="position:relative;width:100%;height:{$height}px;margin:0;padding:0;font-family:DejaVu Sans,sans-serif;overflow:hidden;">
<img src="{$waveUri}" width="100%" height="{$height}" style="position:absolute;left:0;top:0;width:100%;height:{$height}px;display:block;border:0;" alt="" />
<div class="pie-contact" style="position:absolute;right:18px;bottom:14px;z-index:2;text-align:right;max-width:65%;">
{$contactLines}
</div>
</div>
HTML;
    }

    /**
     * @param non-empty-list<string> $pairs Icon URI and variable alternating
     */
    private static function pieContactLines(string ...$pairs): string
    {
        $html = '';
        for ($i = 0; $i < count($pairs); $i += 2) {
            $iconUri = $pairs[$i];
            $variable = $pairs[$i + 1] ?? '';
            $html .= '<div style="text-align:right;line-height:1.1;margin:0 0 1px 0;white-space:normal;word-wrap:break-word;">'
                . '<img src="' . $iconUri . '" width="10" height="10" alt="" style="display:inline-block;vertical-align:middle;margin-right:3px;" />'
                . '<span style="color:#ffffff;font-size:6.5pt;vertical-align:middle;">' . $variable . '</span>'
                . '</div>';
        }

        return $html;
    }

    private static function pieWaveSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 112" preserveAspectRatio="none">'
            . '<path fill="#b8d97e" d="M0,44 C240,4 460,38 680,26 C820,18 880,34 960,28 L960,112 L0,112 Z"/>'
            . '<path fill="#6b7da8" d="M300,54 C520,10 720,44 920,24 C1040,14 1130,32 1200,26 L1200,112 L300,112 Z"/>'
            . '<rect fill="#394b65" x="0" y="104" width="1200" height="8"/>'
            . '</svg>';
    }

    private static function iconSvg(string $path): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'
            . '<path fill="#ffffff" d="' . $path . '"/>'
            . '</svg>';
    }

    private static function svgDataUri(string $svg): string
    {
        return 'data:image/svg+xml,' . rawurlencode($svg);
    }
}

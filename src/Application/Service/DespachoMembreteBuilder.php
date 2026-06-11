<?php



declare(strict_types=1);



namespace App\Application\Service;



use App\Domain\Entity\DespachoConfig;



final class DespachoMembreteBuilder

{

    public function __construct(

        private EscritoVariableResolver $variableResolver,

    ) {

    }



    /**

     * @param array<string, string> $values

     */

    public function renderCabeceraHtml(DespachoConfig $despacho, array $values, ?string $logoPath): string

    {

        if (null !== $despacho->cabeceraHtml() && '' !== trim($despacho->cabeceraHtml())) {

            return $this->substituteMembrete($despacho->cabeceraHtml(), $values, $logoPath, null);

        }



        return $this->buildAutoCabecera($values, $logoPath);

    }



    /**

     * @param array<string, string> $values

     */

    public function renderPieHtml(DespachoConfig $despacho, array $values, ?string $logoPath): string

    {

        if (null !== $despacho->pieHtml() && '' !== trim($despacho->pieHtml())) {

            return $this->substituteMembrete($despacho->pieHtml(), $values, $logoPath, null);

        }



        return $this->buildAutoPie($values);

    }



    /**

     * @param array<string, string> $values

     */

    private function buildAutoCabecera(array $values, ?string $logoPath): string

    {

        $logo = $this->logoImgTag($logoPath);

        $nombre = htmlspecialchars($this->subst('[[NOMBRE_FIRMA]]', $values));

        $subtitulo = htmlspecialchars($this->subst('[[SUBTITULO_PROFESIONAL]]', $values));

        $direccion = htmlspecialchars($this->subst('[[DOMICILIO_DESPACHO]]', $values));



        return <<<HTML

{$logo}

<div class="membrete-nombre">{$nombre}</div>

<div class="membrete-subtitulo">{$subtitulo}</div>

<div>{$direccion}</div>

HTML;

    }



    /**

     * @param array<string, string> $values

     */

    private function buildAutoPie(array $values): string

    {

        $web = htmlspecialchars($this->subst('[[WEB_DESPACHO]]', $values));

        $email = htmlspecialchars($this->subst('[[EMAIL_DESPACHO]]', $values));

        $telefono = htmlspecialchars($this->subst('[[TELEFONO_DESPACHO]]', $values));

        $colegio = htmlspecialchars($this->subst('[[COLEGIO_ABOGADOS]]', $values));

        $nif = htmlspecialchars($this->subst('[[NIF_LETRADA]]', $values));



        return <<<HTML

<div>{$web} — {$email} — {$telefono}</div>

<div>{$colegio} · NIF {$nif}</div>

HTML;

    }



    /**

     * @param array<string, string> $values

     */

    private function substituteMembrete(string $html, array $values, ?string $logoPath, ?string $selloPath): string

    {

        $enriched = $values;

        $enriched['LOGO_DESPACHO'] = $this->logoImgTag($logoPath);

        $enriched['SELLO_DESPACHO'] = $this->logoImgTag($selloPath, 'sello');



        return $this->variableResolver->substitute($html, $enriched);

    }



    private function logoImgTag(?string $imagePath, string $cssClass = 'logo'): string

    {

        if (null === $imagePath || !is_file($imagePath)) {

            return '';

        }



        $mime = mime_content_type($imagePath) ?: 'image/png';

        $data = base64_encode((string) file_get_contents($imagePath));



        return sprintf(
            '<img class="%s" style="display:inline-block;vertical-align:middle;" src="data:%s;base64,%s" alt="">',
            $cssClass,
            $mime,
            $data,
        );

    }



    /**

     * @param array<string, string> $values

     */

    private function subst(string $text, array $values): string

    {

        return $this->variableResolver->substitute($text, $values);

    }

}


<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final readonly class DespachoConfig
{
    public const DEFAULT_ID = '00000000-0000-0000-0000-000000000001';

    public function __construct(
        private string $id,
        private string $nombreFirma,
        private string $nombreLetrada,
        private string $numColegiado,
        private string $direccion,
        private string $ciudad,
        private string $subtituloProfesional = '',
        private string $telefono = '',
        private string $email = '',
        private string $web = '',
        private string $nif = '',
        private string $colegioAbogados = '',
        private string $iban = '',
        private string $entidadBancaria = '',
        private string $titularCuenta = '',
        private ?string $cabeceraHtml = null,
        private ?string $pieHtml = null,
        private ?string $logoPath = null,
        private ?string $selloPath = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function nombreFirma(): string
    {
        return $this->nombreFirma;
    }

    public function nombreLetrada(): string
    {
        return $this->nombreLetrada;
    }

    public function numColegiado(): string
    {
        return $this->numColegiado;
    }

    public function direccion(): string
    {
        return $this->direccion;
    }

    public function ciudad(): string
    {
        return $this->ciudad;
    }

    public function subtituloProfesional(): string
    {
        return $this->subtituloProfesional;
    }

    public function telefono(): string
    {
        return $this->telefono;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function web(): string
    {
        return $this->web;
    }

    public function nif(): string
    {
        return $this->nif;
    }

    public function colegioAbogados(): string
    {
        return $this->colegioAbogados;
    }

    public function iban(): string
    {
        return $this->iban;
    }

    public function entidadBancaria(): string
    {
        return $this->entidadBancaria;
    }

    public function titularCuenta(): string
    {
        return $this->titularCuenta;
    }

    public function cabeceraHtml(): ?string
    {
        return $this->cabeceraHtml;
    }

    public function pieHtml(): ?string
    {
        return $this->pieHtml;
    }

    public function logoPath(): ?string
    {
        return $this->logoPath;
    }

    public function selloPath(): ?string
    {
        return $this->selloPath;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public static function defaults(): self
    {
        return new self(
            self::DEFAULT_ID,
            'Marta Melián Guerra',
            'D.ª MARTA MELIAN GUERRA',
            '7.111',
            'C. Picachos, 43, local 2, 35200 Telde, Las Palmas, España',
            'Las Palmas de Gran Canaria',
            'Abogada y Mediadora',
            '+34 652 292 450',
            'mmguerra.abogada@gmail.com',
            'https://martamelianguerraabogados.com/',
            '44737558-M',
            'Ilustre Colegio de Abogados de Las Palmas',
            'ES46 3076 0770 1329 5326 4823',
            'CAJASIETE CAJA RURAL',
            'MARTA MELIAN GUERRA',
        );
    }

    public function withDatos(
        string $nombreFirma,
        string $nombreLetrada,
        string $numColegiado,
        string $direccion,
        string $ciudad,
        string $subtituloProfesional,
        string $telefono,
        string $email,
        string $web,
        string $nif,
        string $colegioAbogados,
        string $iban,
        string $entidadBancaria,
        string $titularCuenta,
        ?string $cabeceraHtml,
        ?string $pieHtml,
    ): self {
        return new self(
            $this->id,
            $nombreFirma,
            $nombreLetrada,
            $numColegiado,
            $direccion,
            $ciudad,
            $subtituloProfesional,
            $telefono,
            $email,
            $web,
            $nif,
            $colegioAbogados,
            $iban,
            $entidadBancaria,
            $titularCuenta,
            $cabeceraHtml,
            $pieHtml,
            $this->logoPath,
            $this->selloPath,
            $this->updatedAt,
        );
    }

    public function withLogoPath(?string $logoPath): self
    {
        return new self(
            $this->id,
            $this->nombreFirma,
            $this->nombreLetrada,
            $this->numColegiado,
            $this->direccion,
            $this->ciudad,
            $this->subtituloProfesional,
            $this->telefono,
            $this->email,
            $this->web,
            $this->nif,
            $this->colegioAbogados,
            $this->iban,
            $this->entidadBancaria,
            $this->titularCuenta,
            $this->cabeceraHtml,
            $this->pieHtml,
            $logoPath,
            $this->selloPath,
            $this->updatedAt,
        );
    }

    public function withSelloPath(?string $selloPath): self
    {
        return new self(
            $this->id,
            $this->nombreFirma,
            $this->nombreLetrada,
            $this->numColegiado,
            $this->direccion,
            $this->ciudad,
            $this->subtituloProfesional,
            $this->telefono,
            $this->email,
            $this->web,
            $this->nif,
            $this->colegioAbogados,
            $this->iban,
            $this->entidadBancaria,
            $this->titularCuenta,
            $this->cabeceraHtml,
            $this->pieHtml,
            $this->logoPath,
            $selloPath,
            $this->updatedAt,
        );
    }
}

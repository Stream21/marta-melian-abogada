<?php

declare(strict_types=1);

namespace App\Application\Service;

/**
 * Desglose fiscal para Holded.
 * Régimen de la letrada (Las Palmas): operación exenta de IVA/IGIC (0%).
 * Los honorarios acordados son el importe a facturar tal cual.
 */
final class IgicInvoiceCalculator
{
    public function __construct(
        private float $taxPercent = 0.0,
        private string $taxKey = 'exento',
    ) {
    }

    public function taxPercent(): float
    {
        return $this->taxPercent;
    }

    public function taxKey(): string
    {
        return $this->taxKey;
    }

    public function isExempt(): bool
    {
        return $this->taxPercent <= 0.009;
    }

    /**
     * @return array{subtotal: float, taxAmount: float, total: float, taxPercent: float, taxKey: string, taxes: list<string>}
     */
    public function fromTotalWithTax(float $totalWithTax): array
    {
        $total = round(max(0.0, $totalWithTax), 2);

        if ($this->isExempt()) {
            return [
                'subtotal' => $total,
                'taxAmount' => 0.0,
                'total' => $total,
                'taxPercent' => 0.0,
                'taxKey' => $this->taxKey,
                'taxes' => [$this->taxKey],
            ];
        }

        $factor = 1 + ($this->taxPercent / 100);
        $subtotal = round($total / $factor, 2);
        $taxAmount = round($total - $subtotal, 2);

        return [
            'subtotal' => $subtotal,
            'taxAmount' => $taxAmount,
            'total' => $total,
            'taxPercent' => $this->taxPercent,
            'taxKey' => $this->taxKey,
            'taxes' => [$this->taxKey],
        ];
    }
}

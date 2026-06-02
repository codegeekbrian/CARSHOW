<?php
/**
 * Finance display helpers for public vehicle pricing.
 */

function financeSettingNumber(array $settings, string $key, float $fallback, float $min, float $max): float
{
    $value = isset($settings[$key]) ? (float) $settings[$key] : $fallback;
    return max($min, min($max, $value));
}

function monthlyPayment(float $principal, float $annualRatePercent, int $months): float
{
    if ($principal <= 0 || $months < 1) {
        return 0;
    }

    $monthlyRate = ($annualRatePercent / 100) / 12;
    if ($monthlyRate <= 0) {
        return $principal / $months;
    }

    $factor = pow(1 + $monthlyRate, $months);
    return $principal * (($monthlyRate * $factor) / ($factor - 1));
}

function formatKesAmount(float $amount): string
{
    return 'KES ' . number_format((float) round($amount, -3), 0);
}

function formatVehicleCashPrice($price): string
{
    $value = (float) $price;
    return $value > 0 ? 'KES ' . number_format($value, 0) : 'Price on inquiry';
}

function financeEstimateRange($price, array $settings = []): array
{
    $cashPrice = (float) $price;
    if ($cashPrice <= 0) {
        return [
            'available' => false,
            'label' => '',
            'low' => 0,
            'high' => 0,
            'disclaimer' => $settings['finance_disclaimer'] ?? 'Finance estimates are indicative and subject to approval.',
        ];
    }

    $depositPercent = financeSettingNumber($settings, 'finance_deposit_percent', 20, 0, 95);
    $annualRate = financeSettingNumber($settings, 'finance_rate_percent', 16, 0, 60);
    $minTerm = (int) financeSettingNumber($settings, 'finance_min_term_months', 36, 1, 120);
    $maxTerm = (int) financeSettingNumber($settings, 'finance_max_term_months', 60, 1, 120);

    if ($minTerm > $maxTerm) {
        [$minTerm, $maxTerm] = [$maxTerm, $minTerm];
    }

    $principal = $cashPrice * (1 - ($depositPercent / 100));
    $low = monthlyPayment($principal, $annualRate, $maxTerm);
    $high = monthlyPayment($principal, $annualRate, $minTerm);

    return [
        'available' => true,
        'label' => formatKesAmount($low) . ' - ' . str_replace('KES ', '', formatKesAmount($high)) . '/mo est.',
        'low' => $low,
        'high' => $high,
        'disclaimer' => $settings['finance_disclaimer'] ?? 'Finance estimates are indicative and subject to approval.',
    ];
}

function vehicleAvailabilityCta($price): string
{
    return (float) $price > 0 ? 'Check Availability' : 'Check Availability';
}

function vehiclePriceCta($price): string
{
    return (float) $price > 0 ? 'Unlock Best Price' : 'Unlock Best Price';
}

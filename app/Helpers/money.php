<?php

function format_rupiah(float|int|string|null $amount, bool $withSymbol = true): string
{
    $val = (float)($amount ?? 0);
    $formatted = number_format($val, 0, ',', '.');
    return $withSymbol ? 'Rp' . $formatted : $formatted;
}

function parse_rupiah(string|float|int|null $formatted): float
{
    if (is_numeric($formatted)) {
        return (float)$formatted;
    }
    $clean = preg_replace('/[^0-9]/', '', (string)$formatted);
    return (float)$clean;
}

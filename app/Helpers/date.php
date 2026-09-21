<?php

function format_date(?string $dateString): string
{
    if (!$dateString) {
        return '-';
    }
    $ts = strtotime($dateString);
    return $ts ? date('d/m/Y', $ts) : '-';
}

function format_datetime(?string $dateString): string
{
    if (!$dateString) {
        return '-';
    }
    $ts = strtotime($dateString);
    return $ts ? date('d/m/Y H:i', $ts) : '-';
}

function format_time(?string $dateString): string
{
    if (!$dateString) {
        return '-';
    }
    $ts = strtotime($dateString);
    return $ts ? date('H:i', $ts) : '-';
}

function format_minutes(int|float $minutes): string
{
    $m = (int)$minutes;
    $hours = floor($m / 60);
    $remMinutes = $m % 60;
    if ($hours > 0 && $remMinutes > 0) {
        return "{$hours}j {$remMinutes}m";
    } elseif ($hours > 0) {
        return "{$hours} jam";
    }
    return "{$remMinutes} menit";
}

function minutes_to_hours(int|float $minutes): float
{
    return round((float)$minutes / 60, 2);
}

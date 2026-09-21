<?php

function url(string $path = ''): string
{
    return \App\Core\Router::url($path);
}

function asset(string $path = ''): string
{
    $base = \App\Core\Router::getBasePath();
    $cleanPath = '/' . ltrim($path, '/');
    $filePath = dirname(__DIR__, 2) . '/public' . $cleanPath;
    $version = file_exists($filePath) ? '?v=' . filemtime($filePath) : '';
    return $base . $cleanPath . $version;
}

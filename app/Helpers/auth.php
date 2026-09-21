<?php

function auth_check(): bool
{
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function auth_user(): ?array
{
    if (!auth_check()) {
        return null;
    }
    return $_SESSION['user'];
}

function auth_id(): ?int
{
    return auth_check() ? (int)$_SESSION['user']['id'] : null;
}

function auth_role(): ?string
{
    return auth_check() ? ($_SESSION['user']['role'] ?? null) : null;
}

function is_owner(): bool
{
    return auth_role() === 'OWNER';
}

function is_cashier(): bool
{
    return auth_role() === 'CASHIER';
}

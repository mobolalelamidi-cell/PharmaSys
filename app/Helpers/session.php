<?php

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $value;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function user_has_role(array|string $roles): bool
{
    $user = current_user();
    $roles = (array) $roles;

    return $user !== null && in_array($user['role'], $roles, true);
}

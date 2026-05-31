<?php

function require_auth(): void
{
    if (current_user() === null) {
        flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
}

function require_role(array|string $roles): void
{
    require_auth();

    if (!user_has_role($roles)) {
        http_response_code(403);
        require __DIR__ . '/../../templates/errors/403.php';
        exit;
    }
}

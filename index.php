<?php
require_once __DIR__ . '/app/Core/bootstrap.php';

if (current_user() === null) {
    redirect('login.php');
}

redirect('modules/dashboard/index.php');

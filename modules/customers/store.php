<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/customers/create.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/customers/create.php');
}

$data = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'address' => trim($_POST['address'] ?? ''),
];
$errors = validate_customer($data);

if ($errors !== []) {
    $_SESSION['_errors'] = $errors;
    set_old($_POST);
    redirect('modules/customers/create.php');
}

$statement = Database::connection()->prepare(
    'INSERT INTO customers (full_name, phone, address) VALUES (:full_name, :phone, :address)'
);
$statement->execute($data);
$id = (int) Database::connection()->lastInsertId();
audit_log('created customer', 'customers', $id);
clear_old();
flash('success', 'Customer created successfully.');
redirect('modules/customers/index.php');

function validate_customer(array $data): array
{
    $errors = [];
    if ($data['full_name'] === '') {
        $errors[] = 'Customer name is required.';
    }
    if ($data['phone'] !== '' && strlen($data['phone']) < 6) {
        $errors[] = 'Phone number is too short.';
    }
    return $errors;
}

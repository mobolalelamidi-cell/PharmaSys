<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/customers/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/customers/edit.php?id=' . $id);
}

$data = [
    'id' => $id,
    'full_name' => trim($_POST['full_name'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'address' => trim($_POST['address'] ?? ''),
];
$errors = [];

if ($data['id'] <= 0) {
    $errors[] = 'Invalid customer selected.';
}
if ($data['full_name'] === '') {
    $errors[] = 'Customer name is required.';
}
if ($data['phone'] !== '' && strlen($data['phone']) < 6) {
    $errors[] = 'Phone number is too short.';
}

if ($errors !== []) {
    $_SESSION['_errors'] = $errors;
    set_old($_POST);
    redirect('modules/customers/edit.php?id=' . $id);
}

$statement = Database::connection()->prepare(
    'UPDATE customers SET full_name = :full_name, phone = :phone, address = :address WHERE id = :id'
);
$statement->execute($data);
audit_log('updated customer', 'customers', $id);
clear_old();
flash('success', 'Customer updated successfully.');
redirect('modules/customers/index.php');

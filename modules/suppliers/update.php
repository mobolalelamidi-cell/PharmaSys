<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/suppliers/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/suppliers/edit.php?id=' . $id);
}

$data = [
    'id' => $id,
    'company_name' => trim($_POST['company_name'] ?? ''),
    'contact_person' => trim($_POST['contact_person'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'address' => trim($_POST['address'] ?? ''),
];
$errors = [];

if ($data['id'] <= 0) {
    $errors[] = 'Invalid supplier selected.';
}
if ($data['company_name'] === '') {
    $errors[] = 'Company name is required.';
}
if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email address is invalid.';
}

if ($errors !== []) {
    $_SESSION['_errors'] = $errors;
    set_old($_POST);
    redirect('modules/suppliers/edit.php?id=' . $id);
}

$statement = Database::connection()->prepare(
    'UPDATE suppliers SET company_name = :company_name, contact_person = :contact_person, phone = :phone, email = :email, address = :address WHERE id = :id'
);
$statement->execute($data);
audit_log('updated supplier', 'suppliers', $id);
clear_old();
flash('success', 'Supplier updated successfully.');
redirect('modules/suppliers/index.php');

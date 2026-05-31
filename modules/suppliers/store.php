<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/suppliers/create.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/suppliers/create.php');
}

$data = clean_supplier($_POST);
$errors = validate_supplier($data);

if ($errors !== []) {
    $_SESSION['_errors'] = $errors;
    set_old($_POST);
    redirect('modules/suppliers/create.php');
}

$statement = Database::connection()->prepare(
    'INSERT INTO suppliers (company_name, contact_person, phone, email, address) VALUES (:company_name, :contact_person, :phone, :email, :address)'
);
$statement->execute($data);
$id = (int) Database::connection()->lastInsertId();
audit_log('created supplier', 'suppliers', $id);
clear_old();
flash('success', 'Supplier created successfully.');
redirect('modules/suppliers/index.php');

function clean_supplier(array $input): array
{
    return [
        'company_name' => trim($input['company_name'] ?? ''),
        'contact_person' => trim($input['contact_person'] ?? ''),
        'phone' => trim($input['phone'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'address' => trim($input['address'] ?? ''),
    ];
}

function validate_supplier(array $data): array
{
    $errors = [];
    if ($data['company_name'] === '') {
        $errors[] = 'Company name is required.';
    }
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email address is invalid.';
    }
    return $errors;
}

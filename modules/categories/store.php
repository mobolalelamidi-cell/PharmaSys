<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/categories/create.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/categories/create.php');
}

$data = [
    'name' => trim($_POST['name'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
];

if ($data['name'] === '') {
    $_SESSION['_errors'] = ['Category name is required.'];
    set_old($_POST);
    redirect('modules/categories/create.php');
}

try {
    $statement = Database::connection()->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
    $statement->execute($data);
    $id = (int) Database::connection()->lastInsertId();
    audit_log('created category', 'categories', $id);
    clear_old();
    flash('success', 'Category created successfully.');
    redirect('modules/categories/index.php');
} catch (PDOException $exception) {
    $_SESSION['_errors'] = ['Category name already exists.'];
    set_old($_POST);
    redirect('modules/categories/create.php');
}

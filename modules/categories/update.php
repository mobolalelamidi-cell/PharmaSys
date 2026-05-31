<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/categories/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/categories/edit.php?id=' . $id);
}

$data = [
    'id' => $id,
    'name' => trim($_POST['name'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
];

if ($data['id'] <= 0 || $data['name'] === '') {
    $_SESSION['_errors'] = ['Category name is required.'];
    set_old($_POST);
    redirect('modules/categories/edit.php?id=' . $id);
}

try {
    $statement = Database::connection()->prepare('UPDATE categories SET name = :name, description = :description WHERE id = :id');
    $statement->execute($data);
    audit_log('updated category', 'categories', $id);
    clear_old();
    flash('success', 'Category updated successfully.');
    redirect('modules/categories/index.php');
} catch (PDOException $exception) {
    $_SESSION['_errors'] = ['Category name already exists.'];
    set_old($_POST);
    redirect('modules/categories/edit.php?id=' . $id);
}

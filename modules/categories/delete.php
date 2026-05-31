<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/categories/index.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/categories/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

try {
    $statement = Database::connection()->prepare('DELETE FROM categories WHERE id = :id');
    $statement->execute(['id' => $id]);
    audit_log('deleted category', 'categories', $id);
    flash('success', 'Category deleted successfully.');
} catch (PDOException $exception) {
    flash('error', 'This category is linked to medicines and cannot be deleted.');
}

redirect('modules/categories/index.php');

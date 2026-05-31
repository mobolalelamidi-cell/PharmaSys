<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/suppliers/index.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/suppliers/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

try {
    $statement = Database::connection()->prepare('DELETE FROM suppliers WHERE id = :id');
    $statement->execute(['id' => $id]);
    audit_log('deleted supplier', 'suppliers', $id);
    flash('success', 'Supplier deleted successfully.');
} catch (PDOException $exception) {
    flash('error', 'This supplier is linked to medicines or purchases and cannot be deleted.');
}

redirect('modules/suppliers/index.php');

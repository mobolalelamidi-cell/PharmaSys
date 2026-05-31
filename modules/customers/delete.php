<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/customers/index.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/customers/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

try {
    $statement = Database::connection()->prepare('DELETE FROM customers WHERE id = :id');
    $statement->execute(['id' => $id]);
    audit_log('deleted customer', 'customers', $id);
    flash('success', 'Customer deleted successfully.');
} catch (PDOException $exception) {
    flash('error', 'This customer has sales history and cannot be deleted.');
}

redirect('modules/customers/index.php');

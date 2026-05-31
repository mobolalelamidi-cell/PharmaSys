<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/medicines/index.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/medicines/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Invalid medicine selected.');
    redirect('modules/medicines/index.php');
}

$pdo = Database::connection();

try {
    $pdo->beginTransaction();

    $movementDelete = $pdo->prepare('DELETE FROM stock_movements WHERE medicine_id = :id');
    $movementDelete->execute(['id' => $id]);

    $statement = $pdo->prepare('DELETE FROM medicines WHERE id = :id');
    $statement->execute(['id' => $id]);

    audit_log('deleted medicine', 'medicines', $id);
    $pdo->commit();
    flash('success', 'Medicine deleted successfully.');
} catch (PDOException $exception) {
    $pdo->rollBack();
    flash('error', 'This medicine is linked to sales, purchases, or prescriptions and cannot be deleted.');
}

redirect('modules/medicines/index.php');

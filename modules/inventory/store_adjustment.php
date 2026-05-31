<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/inventory/adjust.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/inventory/adjust.php');
}

$medicineId = (int) ($_POST['medicine_id'] ?? 0);
$adjustmentType = trim($_POST['adjustment_type'] ?? '');
$quantity = trim($_POST['quantity'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$errors = [];

if ($medicineId <= 0) {
    $errors[] = 'Medicine is required.';
}

if (!in_array($adjustmentType, ['increase', 'decrease', 'expired'], true)) {
    $errors[] = 'Select a valid adjustment type.';
}

if (!ctype_digit($quantity) || (int) $quantity <= 0) {
    $errors[] = 'Quantity must be a whole number greater than zero.';
}

if ($errors !== []) {
    $_SESSION['_errors'] = $errors;
    set_old($_POST);
    redirect('modules/inventory/adjust.php');
}

$pdo = Database::connection();

try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare('SELECT id, medicine_name, quantity FROM medicines WHERE id = :id LIMIT 1 FOR UPDATE');
    $statement->execute(['id' => $medicineId]);
    $medicine = $statement->fetch();

    if (!$medicine) {
        throw new RuntimeException('Medicine not found.');
    }

    $quantityValue = (int) $quantity;
    $delta = $adjustmentType === 'increase' ? $quantityValue : -$quantityValue;
    $newQuantity = (int) $medicine['quantity'] + $delta;

    if ($newQuantity < 0) {
        throw new RuntimeException($medicine['medicine_name'] . ' has only ' . (int) $medicine['quantity'] . ' units in stock.');
    }

    $update = $pdo->prepare('UPDATE medicines SET quantity = :quantity WHERE id = :id');
    $update->execute([
        'quantity' => $newQuantity,
        'id' => $medicineId,
    ]);

    $movementType = $adjustmentType === 'expired' ? 'expired' : 'adjustment';
    $movement = $pdo->prepare(
        'INSERT INTO stock_movements (medicine_id, movement_type, quantity, reference_id) VALUES (:medicine_id, :movement_type, :quantity, :reference_id)'
    );
    $movement->execute([
        'medicine_id' => $medicineId,
        'movement_type' => $movementType,
        'quantity' => $delta,
        'reference_id' => null,
    ]);

    $action = 'stock ' . $adjustmentType . ' by ' . $quantityValue;
    if ($reason !== '') {
        $action .= ' - ' . $reason;
    }
    audit_log($action, 'medicines', $medicineId);

    $pdo->commit();
    clear_old();
    flash('success', 'Stock adjusted successfully.');
    redirect('modules/inventory/index.php');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['_errors'] = [$exception->getMessage()];
    set_old($_POST);
    redirect('modules/inventory/adjust.php');
}

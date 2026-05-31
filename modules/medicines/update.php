<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/medicines/index.php');
}

$id = (int) ($_POST['id'] ?? 0);

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/medicines/edit.php?id=' . $id);
}

$data = [
    'id' => $id,
    'category_id' => $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null,
    'supplier_id' => $_POST['supplier_id'] !== '' ? (int) $_POST['supplier_id'] : null,
    'medicine_code' => trim($_POST['medicine_code'] ?? ''),
    'medicine_name' => trim($_POST['medicine_name'] ?? ''),
    'generic_name' => trim($_POST['generic_name'] ?? ''),
    'purchase_price' => trim($_POST['purchase_price'] ?? ''),
    'selling_price' => trim($_POST['selling_price'] ?? ''),
    'quantity' => trim($_POST['quantity'] ?? ''),
    'minimum_stock' => trim($_POST['minimum_stock'] ?? ''),
    'manufacturing_date' => $_POST['manufacturing_date'] !== '' ? $_POST['manufacturing_date'] : null,
    'expiry_date' => $_POST['expiry_date'] !== '' ? $_POST['expiry_date'] : null,
    'description' => trim($_POST['description'] ?? ''),
];

$errors = validate_medicine_update($data);

if ($errors !== []) {
    $_SESSION['_errors'] = $errors;
    set_old($_POST);
    redirect('modules/medicines/edit.php?id=' . $id);
}

$pdo = Database::connection();
$currentStatement = $pdo->prepare('SELECT quantity FROM medicines WHERE id = :id LIMIT 1');
$currentStatement->execute(['id' => $id]);
$currentMedicine = $currentStatement->fetch();

if (!$currentMedicine) {
    flash('error', 'Medicine not found.');
    redirect('modules/medicines/index.php');
}

try {
    $pdo->beginTransaction();

    $statement = $pdo->prepare(
        'UPDATE medicines SET
            category_id = :category_id,
            supplier_id = :supplier_id,
            medicine_code = :medicine_code,
            medicine_name = :medicine_name,
            generic_name = :generic_name,
            purchase_price = :purchase_price,
            selling_price = :selling_price,
            quantity = :quantity,
            minimum_stock = :minimum_stock,
            manufacturing_date = :manufacturing_date,
            expiry_date = :expiry_date,
            description = :description
        WHERE id = :id'
    );
    $statement->execute($data);

    $quantityDifference = (int) $data['quantity'] - (int) $currentMedicine['quantity'];
    if ($quantityDifference !== 0) {
        $movement = $pdo->prepare(
            'INSERT INTO stock_movements (medicine_id, movement_type, quantity, reference_id) VALUES (:medicine_id, :movement_type, :quantity, :reference_id)'
        );
        $movement->execute([
            'medicine_id' => $id,
            'movement_type' => 'adjustment',
            'quantity' => $quantityDifference,
            'reference_id' => null,
        ]);
    }

    audit_log('updated medicine', 'medicines', $id);
    $pdo->commit();
    clear_old();
    flash('success', 'Medicine updated successfully.');
    redirect('modules/medicines/index.php');
} catch (PDOException $exception) {
    $pdo->rollBack();
    $_SESSION['_errors'] = [$exception->getCode() === '23000' ? 'Medicine code already exists.' : 'Unable to update medicine.'];
    set_old($_POST);
    redirect('modules/medicines/edit.php?id=' . $id);
}

function validate_medicine_update(array $data): array
{
    $errors = [];

    if ($data['id'] <= 0) {
        $errors[] = 'Invalid medicine selected.';
    }

    if ($data['medicine_code'] === '') {
        $errors[] = 'Medicine code is required.';
    }

    if ($data['medicine_name'] === '') {
        $errors[] = 'Medicine name is required.';
    }

    if (!is_numeric($data['purchase_price']) || (float) $data['purchase_price'] < 0) {
        $errors[] = 'Purchase price must be a valid positive amount.';
    }

    if (!is_numeric($data['selling_price']) || (float) $data['selling_price'] < 0) {
        $errors[] = 'Selling price must be a valid positive amount.';
    }

    if (is_numeric($data['purchase_price']) && is_numeric($data['selling_price']) && (float) $data['selling_price'] < (float) $data['purchase_price']) {
        $errors[] = 'Selling price should not be lower than purchase price.';
    }

    if (!ctype_digit((string) $data['quantity'])) {
        $errors[] = 'Quantity must be a whole number.';
    }

    if (!ctype_digit((string) $data['minimum_stock'])) {
        $errors[] = 'Minimum stock must be a whole number.';
    }

    if ($data['manufacturing_date'] && $data['expiry_date'] && $data['manufacturing_date'] > $data['expiry_date']) {
        $errors[] = 'Expiry date must be after manufacturing date.';
    }

    return $errors;
}

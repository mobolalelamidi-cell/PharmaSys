<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/purchases/create.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/purchases/create.php');
}

$supplierId = (int) ($_POST['supplier_id'] ?? 0);
$reference = trim($_POST['purchase_reference'] ?? '');
$medicineIds = $_POST['medicine_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$prices = $_POST['purchase_price'] ?? [];
$errors = [];
$items = [];

if ($supplierId <= 0) {
    $errors[] = 'Supplier is required.';
}

foreach ($medicineIds as $index => $medicineId) {
    $medicineId = (int) $medicineId;
    $quantity = trim((string) ($quantities[$index] ?? ''));
    $price = trim((string) ($prices[$index] ?? ''));

    if ($medicineId <= 0 && $quantity === '' && $price === '') {
        continue;
    }

    if ($medicineId <= 0) {
        $errors[] = 'Select a medicine for every purchase line.';
    }

    if (!ctype_digit($quantity) || (int) $quantity <= 0) {
        $errors[] = 'Purchase quantities must be whole numbers greater than zero.';
    }

    if (!is_numeric($price) || (float) $price < 0) {
        $errors[] = 'Purchase prices must be valid positive amounts.';
    }

    if ($medicineId > 0 && ctype_digit($quantity) && is_numeric($price)) {
        $items[] = [
            'medicine_id' => $medicineId,
            'quantity' => (int) $quantity,
            'purchase_price' => round((float) $price, 2),
            'subtotal' => round((int) $quantity * (float) $price, 2),
        ];
    }
}

if ($items === []) {
    $errors[] = 'Add at least one purchase item.';
}

if ($errors !== []) {
    $_SESSION['_errors'] = array_values(array_unique($errors));
    set_old($_POST);
    redirect('modules/purchases/create.php');
}

$pdo = Database::connection();

try {
    $pdo->beginTransaction();

    $supplierCheck = $pdo->prepare('SELECT id FROM suppliers WHERE id = :id LIMIT 1');
    $supplierCheck->execute(['id' => $supplierId]);
    if (!$supplierCheck->fetch()) {
        throw new RuntimeException('Supplier not found.');
    }

    $medicineCheck = $pdo->prepare('SELECT id FROM medicines WHERE id = :id LIMIT 1');
    foreach ($items as $item) {
        $medicineCheck->execute(['id' => $item['medicine_id']]);
        if (!$medicineCheck->fetch()) {
            throw new RuntimeException('One selected medicine no longer exists.');
        }
    }

    if ($reference === '') {
        $reference = 'PUR-' . date('Ymd-His') . '-' . random_int(100, 999);
    }

    $totalAmount = array_sum(array_column($items, 'subtotal'));
    $purchase = $pdo->prepare(
        'INSERT INTO purchases (supplier_id, purchase_reference, total_amount) VALUES (:supplier_id, :purchase_reference, :total_amount)'
    );
    $purchase->execute([
        'supplier_id' => $supplierId,
        'purchase_reference' => $reference,
        'total_amount' => $totalAmount,
    ]);
    $purchaseId = (int) $pdo->lastInsertId();

    $itemInsert = $pdo->prepare(
        'INSERT INTO purchase_items (purchase_id, medicine_id, quantity, purchase_price, subtotal) VALUES (:purchase_id, :medicine_id, :quantity, :purchase_price, :subtotal)'
    );
    $stockUpdate = $pdo->prepare(
        'UPDATE medicines SET quantity = quantity + :quantity, purchase_price = :purchase_price, supplier_id = COALESCE(supplier_id, :supplier_id) WHERE id = :medicine_id'
    );
    $movementInsert = $pdo->prepare(
        'INSERT INTO stock_movements (medicine_id, movement_type, quantity, reference_id) VALUES (:medicine_id, :movement_type, :quantity, :reference_id)'
    );

    foreach ($items as $item) {
        $itemInsert->execute([
            'purchase_id' => $purchaseId,
            'medicine_id' => $item['medicine_id'],
            'quantity' => $item['quantity'],
            'purchase_price' => $item['purchase_price'],
            'subtotal' => $item['subtotal'],
        ]);
        $stockUpdate->execute([
            'quantity' => $item['quantity'],
            'purchase_price' => $item['purchase_price'],
            'supplier_id' => $supplierId,
            'medicine_id' => $item['medicine_id'],
        ]);
        $movementInsert->execute([
            'medicine_id' => $item['medicine_id'],
            'movement_type' => 'purchase',
            'quantity' => $item['quantity'],
            'reference_id' => $purchaseId,
        ]);
    }

    audit_log('registered purchase', 'purchases', $purchaseId);
    $pdo->commit();
    clear_old();
    flash('success', 'Purchase registered and stock updated.');
    redirect('modules/purchases/show.php?id=' . $purchaseId);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['_errors'] = [$exception instanceof PDOException && $exception->getCode() === '23000'
        ? 'Purchase reference already exists.'
        : $exception->getMessage()];
    set_old($_POST);
    redirect('modules/purchases/create.php');
}

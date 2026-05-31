<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/sales/create.php');
}

if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
    flash('error', 'Your session expired. Please try again.');
    redirect('modules/sales/create.php');
}

$customerId = $_POST['customer_id'] !== '' ? (int) $_POST['customer_id'] : null;
$paymentMethod = trim($_POST['payment_method'] ?? '');
$amountPaid = trim($_POST['amount_paid'] ?? '');
$medicineIds = $_POST['medicine_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$prices = $_POST['unit_price'] ?? [];
$allowedPayments = ['cash', 'mobile_money', 'card', 'bank_transfer'];
$errors = [];
$items = [];

if (!in_array($paymentMethod, $allowedPayments, true)) {
    $errors[] = 'Select a valid payment method.';
}

if (!is_numeric($amountPaid) || (float) $amountPaid < 0) {
    $errors[] = 'Amount paid must be a valid positive amount.';
}

foreach ($medicineIds as $index => $medicineId) {
    $medicineId = (int) $medicineId;
    $quantity = trim((string) ($quantities[$index] ?? ''));
    $price = trim((string) ($prices[$index] ?? ''));

    if ($medicineId <= 0 && $quantity === '' && $price === '') {
        continue;
    }

    if ($medicineId <= 0) {
        $errors[] = 'Select a medicine for every sale line.';
    }
    if (!ctype_digit($quantity) || (int) $quantity <= 0) {
        $errors[] = 'Sale quantities must be whole numbers greater than zero.';
    }
    if (!is_numeric($price) || (float) $price < 0) {
        $errors[] = 'Unit prices must be valid positive amounts.';
    }

    if ($medicineId > 0 && ctype_digit($quantity) && is_numeric($price)) {
        $items[] = [
            'medicine_id' => $medicineId,
            'quantity' => (int) $quantity,
            'unit_price' => round((float) $price, 2),
            'subtotal' => round((int) $quantity * (float) $price, 2),
        ];
    }
}

if ($items === []) {
    $errors[] = 'Add at least one sale item.';
}

$totalAmount = array_sum(array_column($items, 'subtotal'));
if (is_numeric($amountPaid) && (float) $amountPaid < $totalAmount) {
    $errors[] = 'Amount paid cannot be lower than the sale total.';
}

if ($errors !== []) {
    $_SESSION['_errors'] = array_values(array_unique($errors));
    set_old($_POST);
    redirect('modules/sales/create.php');
}

$pdo = Database::connection();

try {
    $pdo->beginTransaction();

    if ($customerId !== null) {
        $customerCheck = $pdo->prepare('SELECT id FROM customers WHERE id = :id LIMIT 1');
        $customerCheck->execute(['id' => $customerId]);
        if (!$customerCheck->fetch()) {
            throw new RuntimeException('Selected customer was not found.');
        }
    }

    $medicineCheck = $pdo->prepare('SELECT id, medicine_name, quantity FROM medicines WHERE id = :id LIMIT 1 FOR UPDATE');
    foreach ($items as $item) {
        $medicineCheck->execute(['id' => $item['medicine_id']]);
        $medicine = $medicineCheck->fetch();
        if (!$medicine) {
            throw new RuntimeException('One selected medicine no longer exists.');
        }
        if ((int) $medicine['quantity'] < $item['quantity']) {
            throw new RuntimeException($medicine['medicine_name'] . ' has only ' . (int) $medicine['quantity'] . ' units in stock.');
        }
    }

    $invoiceNumber = 'INV-' . date('Ymd-His') . '-' . random_int(100, 999);
    $changeAmount = round((float) $amountPaid - $totalAmount, 2);
    $sale = $pdo->prepare(
        'INSERT INTO sales (invoice_number, customer_id, user_id, total_amount, amount_paid, change_amount, payment_method)
         VALUES (:invoice_number, :customer_id, :user_id, :total_amount, :amount_paid, :change_amount, :payment_method)'
    );
    $sale->execute([
        'invoice_number' => $invoiceNumber,
        'customer_id' => $customerId,
        'user_id' => current_user()['id'],
        'total_amount' => $totalAmount,
        'amount_paid' => round((float) $amountPaid, 2),
        'change_amount' => $changeAmount,
        'payment_method' => $paymentMethod,
    ]);
    $saleId = (int) $pdo->lastInsertId();

    $itemInsert = $pdo->prepare(
        'INSERT INTO sale_items (sale_id, medicine_id, quantity, unit_price, subtotal)
         VALUES (:sale_id, :medicine_id, :quantity, :unit_price, :subtotal)'
    );
    $stockUpdate = $pdo->prepare('UPDATE medicines SET quantity = quantity - :quantity WHERE id = :medicine_id');
    $movementInsert = $pdo->prepare(
        'INSERT INTO stock_movements (medicine_id, movement_type, quantity, reference_id)
         VALUES (:medicine_id, :movement_type, :quantity, :reference_id)'
    );

    foreach ($items as $item) {
        $itemInsert->execute([
            'sale_id' => $saleId,
            'medicine_id' => $item['medicine_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'subtotal' => $item['subtotal'],
        ]);
        $stockUpdate->execute([
            'quantity' => $item['quantity'],
            'medicine_id' => $item['medicine_id'],
        ]);
        $movementInsert->execute([
            'medicine_id' => $item['medicine_id'],
            'movement_type' => 'sale',
            'quantity' => -$item['quantity'],
            'reference_id' => $saleId,
        ]);
    }

    audit_log('processed sale', 'sales', $saleId);
    $pdo->commit();
    clear_old();
    flash('success', 'Sale completed successfully.');
    redirect('modules/sales/show.php?id=' . $saleId);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['_errors'] = [$exception->getMessage()];
    set_old($_POST);
    redirect('modules/sales/create.php');
}

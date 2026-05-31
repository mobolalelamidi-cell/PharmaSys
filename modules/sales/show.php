<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::connection();

$statement = $pdo->prepare(
    'SELECT s.*, COALESCE(c.full_name, "Walk-in customer") AS customer_name, c.phone AS customer_phone, u.full_name AS cashier_name
     FROM sales s
     LEFT JOIN customers c ON c.id = s.customer_id
     INNER JOIN users u ON u.id = s.user_id
     WHERE s.id = :id
     LIMIT 1'
);
$statement->execute(['id' => $id]);
$sale = $statement->fetch();

if (!$sale) {
    flash('error', 'Sale not found.');
    redirect('modules/sales/index.php');
}

$items = $pdo->prepare(
    'SELECT si.*, m.medicine_code, m.medicine_name
     FROM sale_items si
     INNER JOIN medicines m ON m.id = si.medicine_id
     WHERE si.sale_id = :sale_id
     ORDER BY si.id'
);
$items->execute(['sale_id' => $id]);
$saleItems = $items->fetchAll();

$pageTitle = 'Receipt';
$activeModule = 'sales';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <?php require_once __DIR__ . '/../../templates/flash.php'; ?>
    <section class="content-area">
        <section class="panel receipt-panel">
            <div class="panel-header no-print">
                <div>
                    <h2><?php echo e($sale['invoice_number']); ?></h2>
                    <p class="text-muted mb-0">Receipt generated <?php echo e(date('Y-m-d H:i', strtotime($sale['sale_date']))); ?></p>
                </div>
                <div class="action-buttons">
                    <a class="btn btn-light" href="<?php echo url('modules/sales/index.php'); ?>">Back to sales</a>
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print receipt</button>
                </div>
            </div>

            <div class="receipt-header">
                <div>
                    <h2>PharmaSys</h2>
                    <p class="text-muted mb-0">Pharmacy receipt</p>
                </div>
                <div class="text-end">
                    <strong><?php echo e($sale['invoice_number']); ?></strong>
                    <span class="d-block text-muted"><?php echo e(date('Y-m-d H:i', strtotime($sale['sale_date']))); ?></span>
                </div>
            </div>

            <div class="detail-grid mt-3">
                <div>
                    <span>Customer</span>
                    <strong><?php echo e($sale['customer_name']); ?></strong>
                </div>
                <div>
                    <span>Cashier</span>
                    <strong><?php echo e($sale['cashier_name']); ?></strong>
                </div>
                <div>
                    <span>Payment</span>
                    <strong><?php echo e(str_replace('_', ' ', ucfirst($sale['payment_method']))); ?></strong>
                </div>
                <div>
                    <span>Phone</span>
                    <strong><?php echo e($sale['customer_phone'] ?: 'Not set'); ?></strong>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Medicine</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saleItems as $item): ?>
                            <tr>
                                <td><strong><?php echo e($item['medicine_code']); ?></strong></td>
                                <td><?php echo e($item['medicine_name']); ?></td>
                                <td><?php echo (int) $item['quantity']; ?></td>
                                <td><?php echo number_format((float) $item['unit_price'], 2); ?></td>
                                <td><?php echo number_format((float) $item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="receipt-totals">
                <div><span>Total</span><strong><?php echo number_format((float) $sale['total_amount'], 2); ?></strong></div>
                <div><span>Amount paid</span><strong><?php echo number_format((float) $sale['amount_paid'], 2); ?></strong></div>
                <div><span>Change</span><strong><?php echo number_format((float) $sale['change_amount'], 2); ?></strong></div>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

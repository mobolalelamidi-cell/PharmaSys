<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Inventory Report';
$activeModule = 'reports';
$pdo = Database::connection();

$medicines = $pdo->query(
    'SELECT m.*, c.name AS category_name, s.company_name AS supplier_name, (m.quantity * m.purchase_price) AS stock_value
     FROM medicines m
     LEFT JOIN categories c ON c.id = m.category_id
     LEFT JOIN suppliers s ON s.id = m.supplier_id
     ORDER BY m.medicine_name'
)->fetchAll();

$totalUnits = array_sum(array_column($medicines, 'quantity'));
$stockValue = array_sum(array_column($medicines, 'stock_value'));
$lowStock = array_filter($medicines, fn (array $medicine): bool => (int) $medicine['quantity'] <= (int) $medicine['minimum_stock']);

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel report-panel">
            <div class="panel-header no-print">
                <div>
                    <h2>Inventory Report</h2>
                    <p class="text-muted mb-0">Generated <?php echo e(date('Y-m-d H:i')); ?></p>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print / Save PDF</button>
                    <a class="btn btn-light" href="<?php echo url('modules/reports/index.php'); ?>">Back</a>
                </div>
            </div>

            <div class="detail-grid">
                <div><span>Medicines</span><strong><?php echo count($medicines); ?></strong></div>
                <div><span>Total units</span><strong><?php echo number_format((int) $totalUnits); ?></strong></div>
                <div><span>Stock value</span><strong><?php echo number_format((float) $stockValue, 2); ?></strong></div>
                <div><span>Low stock</span><strong><?php echo count($lowStock); ?></strong></div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Medicine</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Qty</th>
                            <th>Min</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $medicine): ?>
                            <tr>
                                <td><strong><?php echo e($medicine['medicine_code']); ?></strong></td>
                                <td><?php echo e($medicine['medicine_name']); ?></td>
                                <td><?php echo e($medicine['category_name'] ?: 'Uncategorized'); ?></td>
                                <td><?php echo e($medicine['supplier_name'] ?: 'No supplier'); ?></td>
                                <td><?php echo (int) $medicine['quantity']; ?></td>
                                <td><?php echo (int) $medicine['minimum_stock']; ?></td>
                                <td><?php echo number_format((float) $medicine['stock_value'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

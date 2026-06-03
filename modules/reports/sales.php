<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Sales Report';
$activeModule = 'reports';
$pdo = Database::connection();
$dateFrom = trim($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = trim($_GET['date_to'] ?? date('Y-m-d'));

$statement = $pdo->prepare(
    'SELECT s.*, COALESCE(c.full_name, "Walk-in customer") AS customer_name, u.full_name AS cashier_name
     FROM sales s
     LEFT JOIN customers c ON c.id = s.customer_id
     INNER JOIN users u ON u.id = s.user_id
     WHERE DATE(s.sale_date) BETWEEN :date_from AND :date_to
     ORDER BY s.sale_date DESC'
);
$statement->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
$sales = $statement->fetchAll();

$totals = [
    'invoices' => count($sales),
    'revenue' => array_sum(array_column($sales, 'total_amount')),
    'paid' => array_sum(array_column($sales, 'amount_paid')),
];

$payments = $pdo->prepare(
    'SELECT payment_method, COUNT(*) AS sale_count, COALESCE(SUM(total_amount), 0) AS total_amount
     FROM sales
     WHERE DATE(sale_date) BETWEEN :date_from AND :date_to
     GROUP BY payment_method
     ORDER BY total_amount DESC'
);
$payments->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
$paymentRows = $payments->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel report-panel">
            <div class="panel-header no-print">
                <div>
                    <h2>Sales Report</h2>
                    <p class="text-muted mb-0"><?php echo e($dateFrom); ?> to <?php echo e($dateTo); ?></p>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print / Save PDF</button>
                    <a class="btn btn-light" href="<?php echo url('modules/reports/index.php'); ?>">Back</a>
                </div>
            </div>

            <form class="filter-bar compact inventory-filter no-print" method="get">
                <input class="form-control" type="date" name="date_from" value="<?php echo e($dateFrom); ?>">
                <input class="form-control" type="date" name="date_to" value="<?php echo e($dateTo); ?>">
                <button class="btn btn-outline-primary" type="submit">Generate</button>
            </form>

            <div class="detail-grid mt-3">
                <div><span>Invoices</span><strong><?php echo number_format($totals['invoices']); ?></strong></div>
                <div><span>Revenue</span><strong><?php echo number_format((float) $totals['revenue'], 2); ?></strong></div>
                <div><span>Amount paid</span><strong><?php echo number_format((float) $totals['paid'], 2); ?></strong></div>
                <div><span>Average sale</span><strong><?php echo $totals['invoices'] > 0 ? number_format((float) $totals['revenue'] / $totals['invoices'], 2) : '0.00'; ?></strong></div>
            </div>

            <div class="row g-4 mt-1">
                <div class="col-lg-8">
                    <div class="table-responsive">
                        <table class="table align-middle data-table">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Cashier</th>
                                    <th>Payment</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td><strong><?php echo e($sale['invoice_number']); ?></strong></td>
                                        <td><?php echo e(date('Y-m-d H:i', strtotime($sale['sale_date']))); ?></td>
                                        <td><?php echo e($sale['customer_name']); ?></td>
                                        <td><?php echo e($sale['cashier_name']); ?></td>
                                        <td><?php echo e(str_replace('_', ' ', ucfirst($sale['payment_method']))); ?></td>
                                        <td><?php echo number_format((float) $sale['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($sales === []): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">No sales found for this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h3 class="report-subtitle">Payment Methods</h3>
                    <div class="list-group list-group-flush">
                        <?php foreach ($paymentRows as $payment): ?>
                            <div class="list-group-item px-0 d-flex justify-content-between">
                                <span><?php echo e(str_replace('_', ' ', ucfirst($payment['payment_method']))); ?> (<?php echo (int) $payment['sale_count']; ?>)</span>
                                <strong><?php echo number_format((float) $payment['total_amount'], 2); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

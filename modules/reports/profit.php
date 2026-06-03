<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Profit Report';
$activeModule = 'reports';
$pdo = Database::connection();
$dateFrom = trim($_GET['date_from'] ?? date('Y-m-01'));
$dateTo = trim($_GET['date_to'] ?? date('Y-m-d'));

$statement = $pdo->prepare(
    'SELECT
        m.medicine_code,
        m.medicine_name,
        SUM(si.quantity) AS quantity_sold,
        SUM(si.subtotal) AS revenue,
        SUM(si.quantity * m.purchase_price) AS estimated_cost,
        SUM(si.subtotal - (si.quantity * m.purchase_price)) AS gross_profit
     FROM sale_items si
     INNER JOIN sales s ON s.id = si.sale_id
     INNER JOIN medicines m ON m.id = si.medicine_id
     WHERE DATE(s.sale_date) BETWEEN :date_from AND :date_to
     GROUP BY m.id, m.medicine_code, m.medicine_name
     ORDER BY gross_profit DESC'
);
$statement->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
$rows = $statement->fetchAll();

$revenue = array_sum(array_column($rows, 'revenue'));
$cost = array_sum(array_column($rows, 'estimated_cost'));
$profit = array_sum(array_column($rows, 'gross_profit'));

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel report-panel">
            <div class="panel-header no-print">
                <div>
                    <h2>Profit Report</h2>
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
                <div><span>Revenue</span><strong><?php echo number_format((float) $revenue, 2); ?></strong></div>
                <div><span>Estimated cost</span><strong><?php echo number_format((float) $cost, 2); ?></strong></div>
                <div><span>Gross profit</span><strong><?php echo number_format((float) $profit, 2); ?></strong></div>
                <div><span>Margin</span><strong><?php echo $revenue > 0 ? number_format(($profit / $revenue) * 100, 1) . '%' : '0.0%'; ?></strong></div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Medicine</th>
                            <th>Qty Sold</th>
                            <th>Revenue</th>
                            <th>Cost</th>
                            <th>Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?php echo e($row['medicine_code']); ?></strong></td>
                                <td><?php echo e($row['medicine_name']); ?></td>
                                <td><?php echo (int) $row['quantity_sold']; ?></td>
                                <td><?php echo number_format((float) $row['revenue'], 2); ?></td>
                                <td><?php echo number_format((float) $row['estimated_cost'], 2); ?></td>
                                <td><?php echo number_format((float) $row['gross_profit'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($rows === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No sale items found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

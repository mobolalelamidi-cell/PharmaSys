<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Reports';
$activeModule = 'reports';
$pdo = Database::connection();
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

$cards = [
    [
        'title' => 'Daily sales',
        'description' => 'Sales totals, receipts, payment methods, and cashier activity.',
        'href' => 'modules/reports/sales.php?date_from=' . $today . '&date_to=' . $today,
        'color' => 'primary',
    ],
    [
        'title' => 'Monthly sales',
        'description' => 'Month-to-date revenue, invoice count, and item movement.',
        'href' => 'modules/reports/sales.php?date_from=' . $monthStart . '&date_to=' . $today,
        'color' => 'success',
    ],
    [
        'title' => 'Inventory report',
        'description' => 'Current stock, stock value, low-stock items, and suppliers.',
        'href' => 'modules/reports/inventory.php',
        'color' => 'warning',
    ],
    [
        'title' => 'Expiry report',
        'description' => 'Expired and soon-to-expire medicines by date and quantity.',
        'href' => 'modules/reports/expiry.php',
        'color' => 'danger',
    ],
    [
        'title' => 'Profit report',
        'description' => 'Gross profit estimate from sale item prices and purchase costs.',
        'href' => 'modules/reports/profit.php?date_from=' . $monthStart . '&date_to=' . $today,
        'color' => 'primary',
    ],
];

$summary = [
    'today_sales' => (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sale_date) = CURDATE()')->fetchColumn(),
    'month_sales' => (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())')->fetchColumn(),
    'stock_value' => (float) $pdo->query('SELECT COALESCE(SUM(quantity * purchase_price), 0) FROM medicines')->fetchColumn(),
    'low_stock' => (int) $pdo->query('SELECT COUNT(*) FROM medicines WHERE quantity <= minimum_stock')->fetchColumn(),
];

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <div class="stats-grid mb-4">
            <article class="stat-card primary">
                <span>Today's sales</span>
                <strong><?php echo number_format($summary['today_sales'], 2); ?></strong>
            </article>
            <article class="stat-card success">
                <span>Monthly sales</span>
                <strong><?php echo number_format($summary['month_sales'], 2); ?></strong>
            </article>
            <article class="stat-card warning">
                <span>Stock value</span>
                <strong><?php echo number_format($summary['stock_value'], 2); ?></strong>
            </article>
            <article class="stat-card danger">
                <span>Low stock</span>
                <strong><?php echo number_format($summary['low_stock']); ?></strong>
            </article>
        </div>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Report Center</h2>
                    <p class="text-muted mb-0">Generate operational reports for sales, inventory, expiry, and profit.</p>
                </div>
            </div>

            <div class="report-grid">
                <?php foreach ($cards as $card): ?>
                    <a class="report-card <?php echo e($card['color']); ?>" href="<?php echo url($card['href']); ?>">
                        <span><?php echo e($card['title']); ?></span>
                        <p><?php echo e($card['description']); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_auth();

$pageTitle = 'Dashboard';
$activeModule = 'dashboard';
$pdo = Database::connection();

$metrics = [
    'medicines' => (int) $pdo->query('SELECT COUNT(*) FROM medicines')->fetchColumn(),
    'suppliers' => (int) $pdo->query('SELECT COUNT(*) FROM suppliers')->fetchColumn(),
    'today_sales' => (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE DATE(sale_date) = CURDATE()')->fetchColumn(),
    'monthly_revenue' => (float) $pdo->query('SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())')->fetchColumn(),
    'low_stock' => (int) $pdo->query('SELECT COUNT(*) FROM medicines WHERE quantity <= minimum_stock')->fetchColumn(),
    'expiring' => (int) $pdo->query('SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)')->fetchColumn(),
];

$lowStock = $pdo->query('SELECT medicine_name, quantity, minimum_stock FROM medicines WHERE quantity <= minimum_stock ORDER BY quantity ASC LIMIT 5')->fetchAll();
$expiring = $pdo->query('SELECT medicine_name, expiry_date, quantity FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY) ORDER BY expiry_date ASC LIMIT 5')->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <?php require_once __DIR__ . '/../../templates/flash.php'; ?>

    <section class="content-area">
        <div class="stats-grid">
            <article class="stat-card">
                <span>Total medicines</span>
                <strong><?php echo number_format($metrics['medicines']); ?></strong>
            </article>
            <article class="stat-card">
                <span>Total suppliers</span>
                <strong><?php echo number_format($metrics['suppliers']); ?></strong>
            </article>
            <article class="stat-card success">
                <span>Today's sales</span>
                <strong><?php echo number_format($metrics['today_sales'], 2); ?></strong>
            </article>
            <article class="stat-card primary">
                <span>Monthly revenue</span>
                <strong><?php echo number_format($metrics['monthly_revenue'], 2); ?></strong>
            </article>
            <article class="stat-card warning">
                <span>Low stock</span>
                <strong><?php echo number_format($metrics['low_stock']); ?></strong>
            </article>
            <article class="stat-card danger">
                <span>Expiring soon</span>
                <strong><?php echo number_format($metrics['expiring']); ?></strong>
            </article>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-7">
                <section class="panel">
                    <div class="panel-header">
                        <h2>Low Stock Medicines</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Quantity</th>
                                    <th>Minimum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStock as $item): ?>
                                    <tr>
                                        <td><?php echo e($item['medicine_name']); ?></td>
                                        <td><span class="badge text-bg-warning"><?php echo (int) $item['quantity']; ?></span></td>
                                        <td><?php echo (int) $item['minimum_stock']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($lowStock === []): ?>
                                    <tr><td colspan="3" class="text-muted">No low stock medicines.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
            <div class="col-lg-5">
                <section class="panel">
                    <div class="panel-header">
                        <h2>Expiry Alerts</h2>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($expiring as $item): ?>
                            <div class="list-group-item px-0">
                                <strong><?php echo e($item['medicine_name']); ?></strong>
                                <span class="d-block text-muted">Expires <?php echo e($item['expiry_date']); ?> - Qty <?php echo (int) $item['quantity']; ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($expiring === []): ?>
                            <p class="text-muted mb-0">No medicines expiring in the next 90 days.</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

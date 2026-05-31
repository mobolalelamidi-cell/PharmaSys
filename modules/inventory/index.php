<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Inventory';
$activeModule = 'inventory';
$pdo = Database::connection();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(m.medicine_name LIKE :search OR m.generic_name LIKE :search OR m.medicine_code LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($status === 'low') {
    $where[] = 'm.quantity <= m.minimum_stock';
} elseif ($status === 'expired') {
    $where[] = 'm.expiry_date IS NOT NULL AND m.expiry_date < CURDATE()';
} elseif ($status === 'expiring') {
    $where[] = 'm.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
} elseif ($status === 'available') {
    $where[] = 'm.quantity > m.minimum_stock';
}

$whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

$stats = [
    'total_units' => (int) $pdo->query('SELECT COALESCE(SUM(quantity), 0) FROM medicines')->fetchColumn(),
    'low_stock' => (int) $pdo->query('SELECT COUNT(*) FROM medicines WHERE quantity <= minimum_stock')->fetchColumn(),
    'expired' => (int) $pdo->query('SELECT COUNT(*) FROM medicines WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE()')->fetchColumn(),
    'expiring' => (int) $pdo->query('SELECT COUNT(*) FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)')->fetchColumn(),
];

$count = $pdo->prepare("SELECT COUNT(*) FROM medicines m {$whereSql}");
$count->execute($params);
$totalRows = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$statement = $pdo->prepare("
    SELECT m.*, c.name AS category_name, s.company_name AS supplier_name
    FROM medicines m
    LEFT JOIN categories c ON c.id = m.category_id
    LEFT JOIN suppliers s ON s.id = m.supplier_id
    {$whereSql}
    ORDER BY m.quantity ASC, m.expiry_date ASC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$medicines = $statement->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <?php require_once __DIR__ . '/../../templates/flash.php'; ?>
    <section class="content-area">
        <div class="stats-grid mb-4">
            <article class="stat-card primary">
                <span>Total units</span>
                <strong><?php echo number_format($stats['total_units']); ?></strong>
            </article>
            <article class="stat-card warning">
                <span>Low stock</span>
                <strong><?php echo number_format($stats['low_stock']); ?></strong>
            </article>
            <article class="stat-card danger">
                <span>Expired</span>
                <strong><?php echo number_format($stats['expired']); ?></strong>
            </article>
            <article class="stat-card success">
                <span>Expiring soon</span>
                <strong><?php echo number_format($stats['expiring']); ?></strong>
            </article>
        </div>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Stock Overview</h2>
                    <p class="text-muted mb-0">Monitor stock quantities, low-stock products, and expiry risk.</p>
                </div>
                <div class="action-buttons">
                    <a class="btn btn-light" href="<?php echo url('modules/inventory/movements.php'); ?>">Movement history</a>
                    <a class="btn btn-primary" href="<?php echo url('modules/inventory/adjust.php'); ?>">Adjust stock</a>
                </div>
            </div>

            <form class="filter-bar compact inventory-filter" method="get">
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search medicines">
                <select class="form-select" name="status">
                    <option value="">All status</option>
                    <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="low" <?php echo $status === 'low' ? 'selected' : ''; ?>>Low stock</option>
                    <option value="expiring" <?php echo $status === 'expiring' ? 'selected' : ''; ?>>Expiring soon</option>
                    <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                </select>
                <button class="btn btn-outline-primary" type="submit">Filter</button>
                <a class="btn btn-light" href="<?php echo url('modules/inventory/index.php'); ?>">Reset</a>
            </form>

            <div class="table-responsive mt-3">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Medicine</th>
                            <th>Category</th>
                            <th>Supplier</th>
                            <th>Stock</th>
                            <th>Expiry</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $medicine): ?>
                            <?php
                            $isLow = (int) $medicine['quantity'] <= (int) $medicine['minimum_stock'];
                            $isExpired = !empty($medicine['expiry_date']) && $medicine['expiry_date'] < date('Y-m-d');
                            $isExpiring = !$isExpired && !empty($medicine['expiry_date']) && $medicine['expiry_date'] <= date('Y-m-d', strtotime('+90 days'));
                            ?>
                            <tr>
                                <td><strong><?php echo e($medicine['medicine_code']); ?></strong></td>
                                <td>
                                    <strong><?php echo e($medicine['medicine_name']); ?></strong>
                                    <span class="d-block text-muted"><?php echo e($medicine['generic_name'] ?: 'No generic name'); ?></span>
                                </td>
                                <td><?php echo e($medicine['category_name'] ?: 'Uncategorized'); ?></td>
                                <td><?php echo e($medicine['supplier_name'] ?: 'No supplier'); ?></td>
                                <td>
                                    <span class="badge <?php echo $isLow ? 'text-bg-warning' : 'text-bg-success'; ?>"><?php echo (int) $medicine['quantity']; ?></span>
                                    <span class="text-muted">min <?php echo (int) $medicine['minimum_stock']; ?></span>
                                </td>
                                <td>
                                    <?php if ($medicine['expiry_date']): ?>
                                        <span class="badge <?php echo $isExpired ? 'text-bg-danger' : ($isExpiring ? 'text-bg-warning' : 'text-bg-light text-dark'); ?>">
                                            <?php echo e($medicine['expiry_date']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo url('modules/inventory/adjust.php?medicine_id=' . (int) $medicine['id']); ?>">Adjust</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($medicines === []): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No inventory records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php $query = http_build_query(array_merge($_GET, ['page' => $i])); ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo url('modules/inventory/index.php?' . $query); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

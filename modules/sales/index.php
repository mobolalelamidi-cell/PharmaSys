<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

$pageTitle = 'Sales';
$activeModule = 'sales';
$pdo = Database::connection();

$search = trim($_GET['search'] ?? '');
$paymentMethod = trim($_GET['payment_method'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(s.invoice_number LIKE :search OR c.full_name LIKE :search OR u.full_name LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($paymentMethod !== '') {
    $where[] = 's.payment_method = :payment_method';
    $params['payment_method'] = $paymentMethod;
}

if ($dateFrom !== '') {
    $where[] = 'DATE(s.sale_date) >= :date_from';
    $params['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = 'DATE(s.sale_date) <= :date_to';
    $params['date_to'] = $dateTo;
}

$whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

$count = $pdo->prepare("
    SELECT COUNT(*)
    FROM sales s
    LEFT JOIN customers c ON c.id = s.customer_id
    INNER JOIN users u ON u.id = s.user_id
    {$whereSql}
");
$count->execute($params);
$totalRows = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$statement = $pdo->prepare("
    SELECT
        s.*,
        COALESCE(c.full_name, 'Walk-in customer') AS customer_name,
        u.full_name AS cashier_name,
        COALESCE(item_counts.item_count, 0) AS item_count
    FROM sales s
    LEFT JOIN customers c ON c.id = s.customer_id
    INNER JOIN users u ON u.id = s.user_id
    LEFT JOIN (
        SELECT sale_id, COUNT(*) AS item_count
        FROM sale_items
        GROUP BY sale_id
    ) item_counts ON item_counts.sale_id = s.id
    {$whereSql}
    ORDER BY s.sale_date DESC, s.id DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$sales = $statement->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <?php require_once __DIR__ . '/../../templates/flash.php'; ?>
    <section class="content-area">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Sales Register</h2>
                    <p class="text-muted mb-0">Process pharmacy sales and print receipts.</p>
                </div>
                <a class="btn btn-primary" href="<?php echo url('modules/sales/create.php'); ?>">New sale</a>
            </div>

            <form class="filter-bar sales-filter" method="get">
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Invoice, customer, cashier">
                <select class="form-select" name="payment_method">
                    <option value="">All payments</option>
                    <?php foreach (['cash' => 'Cash', 'mobile_money' => 'Mobile Money', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'] as $value => $label): ?>
                        <option value="<?php echo e($value); ?>" <?php echo $paymentMethod === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="form-control" type="date" name="date_from" value="<?php echo e($dateFrom); ?>">
                <input class="form-control" type="date" name="date_to" value="<?php echo e($dateTo); ?>">
                <button class="btn btn-outline-primary" type="submit">Filter</button>
                <a class="btn btn-light" href="<?php echo url('modules/sales/index.php'); ?>">Reset</a>
            </form>

            <div class="table-responsive mt-3">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Cashier</th>
                            <th>Date</th>
                            <th>Payment</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><strong><?php echo e($sale['invoice_number']); ?></strong></td>
                                <td><?php echo e($sale['customer_name']); ?></td>
                                <td><?php echo e($sale['cashier_name']); ?></td>
                                <td><?php echo e(date('Y-m-d H:i', strtotime($sale['sale_date']))); ?></td>
                                <td><span class="badge text-bg-light text-dark"><?php echo e(str_replace('_', ' ', ucfirst($sale['payment_method'] ?? 'Not set'))); ?></span></td>
                                <td><?php echo (int) $sale['item_count']; ?></td>
                                <td><?php echo number_format((float) $sale['total_amount'], 2); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo url('modules/sales/show.php?id=' . (int) $sale['id']); ?>">Receipt</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($sales === []): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">No sales found.</td></tr>
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
                                <a class="page-link" href="<?php echo url('modules/sales/index.php?' . $query); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

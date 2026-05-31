<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Purchases';
$activeModule = 'purchases';
$pdo = Database::connection();

$search = trim($_GET['search'] ?? '');
$supplierId = (int) ($_GET['supplier_id'] ?? 0);
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = 'p.purchase_reference LIKE :search';
    $params['search'] = '%' . $search . '%';
}

if ($supplierId > 0) {
    $where[] = 'p.supplier_id = :supplier_id';
    $params['supplier_id'] = $supplierId;
}

if ($dateFrom !== '') {
    $where[] = 'DATE(p.purchase_date) >= :date_from';
    $params['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $where[] = 'DATE(p.purchase_date) <= :date_to';
    $params['date_to'] = $dateTo;
}

$whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

$count = $pdo->prepare("SELECT COUNT(*) FROM purchases p {$whereSql}");
$count->execute($params);
$totalRows = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$statement = $pdo->prepare("
    SELECT
        p.*,
        s.company_name,
        COALESCE(item_counts.item_count, 0) AS item_count
    FROM purchases p
    INNER JOIN suppliers s ON s.id = p.supplier_id
    LEFT JOIN (
        SELECT purchase_id, COUNT(*) AS item_count
        FROM purchase_items
        GROUP BY purchase_id
    ) item_counts ON item_counts.purchase_id = p.id
    {$whereSql}
    ORDER BY p.purchase_date DESC, p.id DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$purchases = $statement->fetchAll();
$suppliers = $pdo->query('SELECT id, company_name FROM suppliers ORDER BY company_name')->fetchAll();

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
                    <h2>Purchase Register</h2>
                    <p class="text-muted mb-0">Track supplier purchases and automatic stock increases.</p>
                </div>
                <a class="btn btn-primary" href="<?php echo url('modules/purchases/create.php'); ?>">New purchase</a>
            </div>

            <form class="filter-bar purchases-filter" method="get">
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Reference">
                <select class="form-select" name="supplier_id">
                    <option value="0">All suppliers</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo (int) $supplier['id']; ?>" <?php echo $supplierId === (int) $supplier['id'] ? 'selected' : ''; ?>>
                            <?php echo e($supplier['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input class="form-control" type="date" name="date_from" value="<?php echo e($dateFrom); ?>">
                <input class="form-control" type="date" name="date_to" value="<?php echo e($dateTo); ?>">
                <button class="btn btn-outline-primary" type="submit">Filter</button>
                <a class="btn btn-light" href="<?php echo url('modules/purchases/index.php'); ?>">Reset</a>
            </form>

            <div class="table-responsive mt-3">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases as $purchase): ?>
                            <tr>
                                <td><strong><?php echo e($purchase['purchase_reference']); ?></strong></td>
                                <td><?php echo e($purchase['company_name']); ?></td>
                                <td><?php echo e(date('Y-m-d H:i', strtotime($purchase['purchase_date']))); ?></td>
                                <td><span class="badge text-bg-light text-dark"><?php echo (int) $purchase['item_count']; ?></span></td>
                                <td><?php echo number_format((float) $purchase['total_amount'], 2); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo url('modules/purchases/show.php?id=' . (int) $purchase['id']); ?>">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($purchases === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No purchases found.</td></tr>
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
                                <a class="page-link" href="<?php echo url('modules/purchases/index.php?' . $query); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Stock Movements';
$activeModule = 'inventory';
$pdo = Database::connection();
$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;
$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(m.medicine_name LIKE :search OR m.medicine_code LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if (in_array($type, ['purchase', 'sale', 'adjustment', 'expired'], true)) {
    $where[] = 'sm.movement_type = :type';
    $params['type'] = $type;
}

$whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
$count = $pdo->prepare("SELECT COUNT(*) FROM stock_movements sm INNER JOIN medicines m ON m.id = sm.medicine_id {$whereSql}");
$count->execute($params);
$totalRows = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$statement = $pdo->prepare("
    SELECT sm.*, m.medicine_code, m.medicine_name
    FROM stock_movements sm
    INNER JOIN medicines m ON m.id = sm.medicine_id
    {$whereSql}
    ORDER BY sm.movement_date DESC, sm.id DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$movements = $statement->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Movement History</h2>
                    <p class="text-muted mb-0">Trace every purchase, sale, and manual adjustment.</p>
                </div>
                <a class="btn btn-light" href="<?php echo url('modules/inventory/index.php'); ?>">Back to inventory</a>
            </div>

            <form class="filter-bar compact inventory-filter" method="get">
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search medicine">
                <select class="form-select" name="type">
                    <option value="">All movements</option>
                    <?php foreach (['purchase', 'sale', 'adjustment', 'expired'] as $movementType): ?>
                        <option value="<?php echo e($movementType); ?>" <?php echo $type === $movementType ? 'selected' : ''; ?>>
                            <?php echo e(ucfirst($movementType)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Filter</button>
                <a class="btn btn-light" href="<?php echo url('modules/inventory/movements.php'); ?>">Reset</a>
            </form>

            <div class="table-responsive mt-3">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Medicine</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td><?php echo e(date('Y-m-d H:i', strtotime($movement['movement_date']))); ?></td>
                                <td>
                                    <strong><?php echo e($movement['medicine_name']); ?></strong>
                                    <span class="d-block text-muted"><?php echo e($movement['medicine_code']); ?></span>
                                </td>
                                <td><span class="badge text-bg-light text-dark"><?php echo e(ucfirst($movement['movement_type'])); ?></span></td>
                                <td class="<?php echo (int) $movement['quantity'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                    <strong><?php echo (int) $movement['quantity']; ?></strong>
                                </td>
                                <td><?php echo $movement['reference_id'] ? (int) $movement['reference_id'] : '<span class="text-muted">Manual</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($movements === []): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No stock movements found.</td></tr>
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
                                <a class="page-link" href="<?php echo url('modules/inventory/movements.php?' . $query); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

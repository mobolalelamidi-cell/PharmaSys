<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Medicines';
$activeModule = 'medicines';
$pdo = Database::connection();

$search = trim($_GET['search'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);
$supplierId = (int) ($_GET['supplier_id'] ?? 0);
$stock = $_GET['stock'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(m.medicine_name LIKE :search OR m.generic_name LIKE :search OR m.medicine_code LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($categoryId > 0) {
    $where[] = 'm.category_id = :category_id';
    $params['category_id'] = $categoryId;
}

if ($supplierId > 0) {
    $where[] = 'm.supplier_id = :supplier_id';
    $params['supplier_id'] = $supplierId;
}

if ($stock === 'low') {
    $where[] = 'm.quantity <= m.minimum_stock';
} elseif ($stock === 'expired') {
    $where[] = 'm.expiry_date IS NOT NULL AND m.expiry_date < CURDATE()';
} elseif ($stock === 'expiring') {
    $where[] = 'm.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)';
}

$whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

$countStatement = $pdo->prepare("SELECT COUNT(*) FROM medicines m {$whereSql}");
$countStatement->execute($params);
$totalRows = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$sql = "
    SELECT
        m.*,
        c.name AS category_name,
        s.company_name AS supplier_name
    FROM medicines m
    LEFT JOIN categories c ON c.id = m.category_id
    LEFT JOIN suppliers s ON s.id = m.supplier_id
    {$whereSql}
    ORDER BY m.created_at DESC, m.id DESC
    LIMIT :limit OFFSET :offset
";

$statement = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$medicines = $statement->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
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
                    <h2>Medicine Inventory</h2>
                    <p class="text-muted mb-0">Manage stock, pricing, suppliers, and expiry dates.</p>
                </div>
                <a class="btn btn-primary" href="<?php echo url('modules/medicines/create.php'); ?>">Add medicine</a>
            </div>

            <form class="filter-bar" method="get">
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search by name, generic, or code">
                <select class="form-select" name="category_id">
                    <option value="0">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>" <?php echo $categoryId === (int) $category['id'] ? 'selected' : ''; ?>>
                            <?php echo e($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select" name="supplier_id">
                    <option value="0">All suppliers</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo (int) $supplier['id']; ?>" <?php echo $supplierId === (int) $supplier['id'] ? 'selected' : ''; ?>>
                            <?php echo e($supplier['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="form-select" name="stock">
                    <option value="">All stock</option>
                    <option value="low" <?php echo $stock === 'low' ? 'selected' : ''; ?>>Low stock</option>
                    <option value="expiring" <?php echo $stock === 'expiring' ? 'selected' : ''; ?>>Expiring soon</option>
                    <option value="expired" <?php echo $stock === 'expired' ? 'selected' : ''; ?>>Expired</option>
                </select>
                <button class="btn btn-outline-primary" type="submit">Filter</button>
                <a class="btn btn-light" href="<?php echo url('modules/medicines/index.php'); ?>">Reset</a>
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
                            <th>Selling Price</th>
                            <th>Expiry</th>
                            <th class="text-end">Actions</th>
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
                                    <span class="badge <?php echo $isLow ? 'text-bg-warning' : 'text-bg-success'; ?>">
                                        <?php echo (int) $medicine['quantity']; ?>
                                    </span>
                                    <span class="text-muted">min <?php echo (int) $medicine['minimum_stock']; ?></span>
                                </td>
                                <td><?php echo number_format((float) $medicine['selling_price'], 2); ?></td>
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
                                    <div class="action-buttons">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('modules/medicines/edit.php?id=' . (int) $medicine['id']); ?>">Edit</a>
                                        <form method="post" action="<?php echo url('modules/medicines/delete.php'); ?>" onsubmit="return confirm('Delete this medicine?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $medicine['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($medicines === []): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No medicines found.</td>
                            </tr>
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
                                <a class="page-link" href="<?php echo url('modules/medicines/index.php?' . $query); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

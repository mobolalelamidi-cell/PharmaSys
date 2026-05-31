<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

$pageTitle = 'Customers';
$activeModule = 'customers';
$pdo = Database::connection();
$search = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$whereSql = '';
$params = [];

if ($search !== '') {
    $whereSql = 'WHERE c.full_name LIKE :search OR c.phone LIKE :search OR c.address LIKE :search';
    $params['search'] = '%' . $search . '%';
}

$count = $pdo->prepare("SELECT COUNT(*) FROM customers c {$whereSql}");
$count->execute($params);
$totalRows = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$statement = $pdo->prepare("
    SELECT
        c.*,
        COALESCE(sale_stats.sale_count, 0) AS sale_count,
        COALESCE(sale_stats.total_spent, 0) AS total_spent,
        sale_stats.last_purchase
    FROM customers c
    LEFT JOIN (
        SELECT customer_id, COUNT(*) AS sale_count, SUM(total_amount) AS total_spent, MAX(sale_date) AS last_purchase
        FROM sales
        WHERE customer_id IS NOT NULL
        GROUP BY customer_id
    ) sale_stats ON sale_stats.customer_id = c.id
    {$whereSql}
    ORDER BY c.created_at DESC, c.id DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$customers = $statement->fetchAll();

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
                    <h2>Customers</h2>
                    <p class="text-muted mb-0">Manage customer profiles and purchase history.</p>
                </div>
                <a class="btn btn-primary" href="<?php echo url('modules/customers/create.php'); ?>">Add customer</a>
            </div>

            <form class="filter-bar compact" method="get">
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search customers">
                <button class="btn btn-outline-primary" type="submit">Search</button>
                <a class="btn btn-light" href="<?php echo url('modules/customers/index.php'); ?>">Reset</a>
            </form>

            <div class="table-responsive mt-3">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Sales</th>
                            <th>Total spent</th>
                            <th>Last purchase</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($customer['full_name'] ?: 'Unnamed customer'); ?></strong>
                                    <span class="d-block text-muted"><?php echo e($customer['address'] ?: 'No address'); ?></span>
                                </td>
                                <td><?php echo e($customer['phone'] ?: 'Not set'); ?></td>
                                <td><span class="badge text-bg-light text-dark"><?php echo (int) $customer['sale_count']; ?></span></td>
                                <td><?php echo number_format((float) $customer['total_spent'], 2); ?></td>
                                <td><?php echo $customer['last_purchase'] ? e(date('Y-m-d', strtotime($customer['last_purchase']))) : '<span class="text-muted">Never</span>'; ?></td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo url('modules/customers/show.php?id=' . (int) $customer['id']); ?>">View</a>
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('modules/customers/edit.php?id=' . (int) $customer['id']); ?>">Edit</a>
                                        <form method="post" action="<?php echo url('modules/customers/delete.php'); ?>" onsubmit="return confirm('Delete this customer?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $customer['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($customers === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No customers found.</td></tr>
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
                                <a class="page-link" href="<?php echo url('modules/customers/index.php?' . $query); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

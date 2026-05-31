<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Suppliers';
$activeModule = 'suppliers';
$pdo = Database::connection();
$search = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$whereSql = '';
$params = [];

if ($search !== '') {
    $whereSql = 'WHERE company_name LIKE :search OR contact_person LIKE :search OR phone LIKE :search OR email LIKE :search';
    $params['search'] = '%' . $search . '%';
}

$count = $pdo->prepare("SELECT COUNT(*) FROM suppliers {$whereSql}");
$count->execute($params);
$totalRows = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$statement = $pdo->prepare("
    SELECT
        s.*,
        COUNT(DISTINCT m.id) AS medicine_count,
        COUNT(DISTINCT p.id) AS purchase_count
    FROM suppliers s
    LEFT JOIN medicines m ON m.supplier_id = s.id
    LEFT JOIN purchases p ON p.supplier_id = s.id
    {$whereSql}
    GROUP BY s.id
    ORDER BY s.created_at DESC, s.id DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $value) {
    $statement->bindValue(':' . $key, $value);
}
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$suppliers = $statement->fetchAll();

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
                    <h2>Suppliers</h2>
                    <p class="text-muted mb-0">Manage supplier contacts and supply relationships.</p>
                </div>
                <a class="btn btn-primary" href="<?php echo url('modules/suppliers/create.php'); ?>">Add supplier</a>
            </div>

            <form class="filter-bar compact" method="get">
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search suppliers">
                <button class="btn btn-outline-primary" type="submit">Search</button>
                <a class="btn btn-light" href="<?php echo url('modules/suppliers/index.php'); ?>">Reset</a>
            </form>

            <div class="table-responsive mt-3">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Medicines</th>
                            <th>Purchases</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($supplier['company_name']); ?></strong>
                                    <span class="d-block text-muted"><?php echo e($supplier['address'] ?: 'No address'); ?></span>
                                </td>
                                <td><?php echo e($supplier['contact_person'] ?: 'Not set'); ?></td>
                                <td><?php echo e($supplier['phone'] ?: 'Not set'); ?></td>
                                <td><?php echo e($supplier['email'] ?: 'Not set'); ?></td>
                                <td><span class="badge text-bg-light text-dark"><?php echo (int) $supplier['medicine_count']; ?></span></td>
                                <td><span class="badge text-bg-light text-dark"><?php echo (int) $supplier['purchase_count']; ?></span></td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('modules/suppliers/edit.php?id=' . (int) $supplier['id']); ?>">Edit</a>
                                        <form method="post" action="<?php echo url('modules/suppliers/delete.php'); ?>" onsubmit="return confirm('Delete this supplier?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $supplier['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($suppliers === []): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No suppliers found.</td></tr>
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
                                <a class="page-link" href="<?php echo url('modules/suppliers/index.php?' . $query); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

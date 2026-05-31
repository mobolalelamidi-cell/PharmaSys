<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Categories';
$activeModule = 'categories';
$pdo = Database::connection();
$search = trim($_GET['search'] ?? '');
$whereSql = '';
$params = [];

if ($search !== '') {
    $whereSql = 'WHERE c.name LIKE :search OR c.description LIKE :search';
    $params['search'] = '%' . $search . '%';
}

$statement = $pdo->prepare("
    SELECT c.*, COUNT(m.id) AS medicine_count
    FROM categories c
    LEFT JOIN medicines m ON m.category_id = c.id
    {$whereSql}
    GROUP BY c.id
    ORDER BY c.name
");
$statement->execute($params);
$categories = $statement->fetchAll();

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
                    <h2>Categories</h2>
                    <p class="text-muted mb-0">Organize medicines by therapeutic or business category.</p>
                </div>
                <a class="btn btn-primary" href="<?php echo url('modules/categories/create.php'); ?>">Add category</a>
            </div>

            <form class="filter-bar compact" method="get">
                <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search categories">
                <button class="btn btn-outline-primary" type="submit">Search</button>
                <a class="btn btn-light" href="<?php echo url('modules/categories/index.php'); ?>">Reset</a>
            </form>

            <div class="table-responsive mt-3">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Medicines</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo e($category['name']); ?></strong></td>
                                <td><?php echo e($category['description'] ?: 'No description'); ?></td>
                                <td><span class="badge text-bg-light text-dark"><?php echo (int) $category['medicine_count']; ?></span></td>
                                <td class="text-end">
                                    <div class="action-buttons">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo url('modules/categories/edit.php?id=' . (int) $category['id']); ?>">Edit</a>
                                        <form method="post" action="<?php echo url('modules/categories/delete.php'); ?>" onsubmit="return confirm('Delete this category?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int) $category['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($categories === []): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No categories found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

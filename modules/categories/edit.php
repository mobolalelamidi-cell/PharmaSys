<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$id = (int) ($_GET['id'] ?? 0);
$statement = Database::connection()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
$statement->execute(['id' => $id]);
$category = $statement->fetch();

if (!$category) {
    flash('error', 'Category not found.');
    redirect('modules/categories/index.php');
}

$pageTitle = 'Edit Category';
$activeModule = 'categories';
$errors = $_SESSION['_errors'] ?? [];
unset($_SESSION['_errors']);

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel">
            <div class="panel-header"><h2>Edit Category</h2></div>
            <?php
            $action = url('modules/categories/update.php');
            $submitLabel = 'Update category';
            require __DIR__ . '/_form.php';
            ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

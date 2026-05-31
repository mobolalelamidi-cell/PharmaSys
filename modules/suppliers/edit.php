<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$id = (int) ($_GET['id'] ?? 0);
$statement = Database::connection()->prepare('SELECT * FROM suppliers WHERE id = :id LIMIT 1');
$statement->execute(['id' => $id]);
$supplier = $statement->fetch();

if (!$supplier) {
    flash('error', 'Supplier not found.');
    redirect('modules/suppliers/index.php');
}

$pageTitle = 'Edit Supplier';
$activeModule = 'suppliers';
$errors = $_SESSION['_errors'] ?? [];
unset($_SESSION['_errors']);

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Edit Supplier</h2>
                    <p class="text-muted mb-0"><?php echo e($supplier['company_name']); ?></p>
                </div>
            </div>
            <?php
            $action = url('modules/suppliers/update.php');
            $submitLabel = 'Update supplier';
            require __DIR__ . '/_form.php';
            ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

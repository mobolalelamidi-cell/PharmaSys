<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

$id = (int) ($_GET['id'] ?? 0);
$statement = Database::connection()->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
$statement->execute(['id' => $id]);
$customer = $statement->fetch();

if (!$customer) {
    flash('error', 'Customer not found.');
    redirect('modules/customers/index.php');
}

$pageTitle = 'Edit Customer';
$activeModule = 'customers';
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
                    <h2>Edit Customer</h2>
                    <p class="text-muted mb-0"><?php echo e($customer['full_name']); ?></p>
                </div>
            </div>
            <?php
            $action = url('modules/customers/update.php');
            $submitLabel = 'Update customer';
            require __DIR__ . '/_form.php';
            ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

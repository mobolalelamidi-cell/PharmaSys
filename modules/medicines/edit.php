<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::connection();
$statement = $pdo->prepare('SELECT * FROM medicines WHERE id = :id LIMIT 1');
$statement->execute(['id' => $id]);
$medicine = $statement->fetch();

if (!$medicine) {
    flash('error', 'Medicine not found.');
    redirect('modules/medicines/index.php');
}

$pageTitle = 'Edit Medicine';
$activeModule = 'medicines';
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$suppliers = $pdo->query('SELECT id, company_name FROM suppliers ORDER BY company_name')->fetchAll();
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
                    <h2>Edit Medicine</h2>
                    <p class="text-muted mb-0"><?php echo e($medicine['medicine_name']); ?></p>
                </div>
            </div>
            <?php
            $action = url('modules/medicines/update.php');
            $submitLabel = 'Update medicine';
            require __DIR__ . '/_form.php';
            ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

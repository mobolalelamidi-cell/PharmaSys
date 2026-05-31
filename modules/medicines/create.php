<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Add Medicine';
$activeModule = 'medicines';
$pdo = Database::connection();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$suppliers = $pdo->query('SELECT id, company_name FROM suppliers ORDER BY company_name')->fetchAll();
$medicine = [];
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
                    <h2>Add Medicine</h2>
                    <p class="text-muted mb-0">Create a new inventory item with pricing and stock details.</p>
                </div>
            </div>
            <?php
            $action = url('modules/medicines/store.php');
            $submitLabel = 'Create medicine';
            require __DIR__ . '/_form.php';
            ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

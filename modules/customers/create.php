<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

$pageTitle = 'Add Customer';
$activeModule = 'customers';
$customer = [];
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
                    <h2>Add Customer</h2>
                    <p class="text-muted mb-0">Create a customer profile for sales history.</p>
                </div>
            </div>
            <?php
            $action = url('modules/customers/store.php');
            $submitLabel = 'Create customer';
            require __DIR__ . '/_form.php';
            ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

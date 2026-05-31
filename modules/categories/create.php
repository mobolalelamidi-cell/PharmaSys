<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Add Category';
$activeModule = 'categories';
$category = [];
$errors = $_SESSION['_errors'] ?? [];
unset($_SESSION['_errors']);

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel">
            <div class="panel-header"><h2>Add Category</h2></div>
            <?php
            $action = url('modules/categories/store.php');
            $submitLabel = 'Create category';
            require __DIR__ . '/_form.php';
            ?>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

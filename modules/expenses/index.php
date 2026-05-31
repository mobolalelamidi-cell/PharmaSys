<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role('admin');

$pageTitle = 'Expenses';
$activeModule = 'expenses';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel">
            <div class="panel-header">
                <h2>Expenses</h2>
                <button class="btn btn-primary">Record expense</button>
            </div>
            <p class="text-muted mb-0">Rent, electricity, salaries, transport, miscellaneous expenses, and reports will be implemented here.</p>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

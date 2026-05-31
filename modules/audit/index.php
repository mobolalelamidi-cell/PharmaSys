<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role('admin');

$pageTitle = 'Audit Logs';
$activeModule = 'audit';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel">
            <div class="panel-header"><h2>Audit Logs</h2></div>
            <p class="text-muted mb-0">User actions, record changes, login history, and sensitive operations will be shown here.</p>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

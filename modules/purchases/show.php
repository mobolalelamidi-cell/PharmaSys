<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::connection();

$statement = $pdo->prepare(
    'SELECT p.*, s.company_name, s.contact_person, s.phone, s.email
     FROM purchases p
     INNER JOIN suppliers s ON s.id = p.supplier_id
     WHERE p.id = :id
     LIMIT 1'
);
$statement->execute(['id' => $id]);
$purchase = $statement->fetch();

if (!$purchase) {
    flash('error', 'Purchase not found.');
    redirect('modules/purchases/index.php');
}

$items = $pdo->prepare(
    'SELECT pi.*, m.medicine_code, m.medicine_name
     FROM purchase_items pi
     INNER JOIN medicines m ON m.id = pi.medicine_id
     WHERE pi.purchase_id = :purchase_id
     ORDER BY pi.id'
);
$items->execute(['purchase_id' => $id]);
$purchaseItems = $items->fetchAll();

$pageTitle = 'Purchase Details';
$activeModule = 'purchases';
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
                    <h2><?php echo e($purchase['purchase_reference']); ?></h2>
                    <p class="text-muted mb-0">Registered <?php echo e(date('Y-m-d H:i', strtotime($purchase['purchase_date']))); ?></p>
                </div>
                <a class="btn btn-light" href="<?php echo url('modules/purchases/index.php'); ?>">Back to purchases</a>
            </div>

            <div class="detail-grid">
                <div>
                    <span>Supplier</span>
                    <strong><?php echo e($purchase['company_name']); ?></strong>
                </div>
                <div>
                    <span>Contact</span>
                    <strong><?php echo e($purchase['contact_person'] ?: 'Not set'); ?></strong>
                </div>
                <div>
                    <span>Phone</span>
                    <strong><?php echo e($purchase['phone'] ?: 'Not set'); ?></strong>
                </div>
                <div>
                    <span>Total amount</span>
                    <strong><?php echo number_format((float) $purchase['total_amount'], 2); ?></strong>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Medicine</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchaseItems as $item): ?>
                            <tr>
                                <td><strong><?php echo e($item['medicine_code']); ?></strong></td>
                                <td><?php echo e($item['medicine_name']); ?></td>
                                <td><?php echo (int) $item['quantity']; ?></td>
                                <td><?php echo number_format((float) $item['purchase_price'], 2); ?></td>
                                <td><?php echo number_format((float) $item['subtotal'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

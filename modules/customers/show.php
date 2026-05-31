<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

$id = (int) ($_GET['id'] ?? 0);
$pdo = Database::connection();
$statement = $pdo->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
$statement->execute(['id' => $id]);
$customer = $statement->fetch();

if (!$customer) {
    flash('error', 'Customer not found.');
    redirect('modules/customers/index.php');
}

$summary = $pdo->prepare(
    'SELECT COUNT(*) AS sale_count, COALESCE(SUM(total_amount), 0) AS total_spent, MAX(sale_date) AS last_purchase
     FROM sales
     WHERE customer_id = :customer_id'
);
$summary->execute(['customer_id' => $id]);
$stats = $summary->fetch();

$sales = $pdo->prepare(
    'SELECT id, invoice_number, total_amount, amount_paid, change_amount, payment_method, sale_date
     FROM sales
     WHERE customer_id = :customer_id
     ORDER BY sale_date DESC
     LIMIT 20'
);
$sales->execute(['customer_id' => $id]);
$saleHistory = $sales->fetchAll();

$pageTitle = 'Customer Details';
$activeModule = 'customers';
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
                    <h2><?php echo e($customer['full_name']); ?></h2>
                    <p class="text-muted mb-0"><?php echo e($customer['phone'] ?: 'No phone number'); ?></p>
                </div>
                <div class="action-buttons">
                    <a class="btn btn-light" href="<?php echo url('modules/customers/index.php'); ?>">Back</a>
                    <a class="btn btn-primary" href="<?php echo url('modules/customers/edit.php?id=' . (int) $customer['id']); ?>">Edit customer</a>
                </div>
            </div>

            <div class="detail-grid">
                <div>
                    <span>Total sales</span>
                    <strong><?php echo (int) $stats['sale_count']; ?></strong>
                </div>
                <div>
                    <span>Total spent</span>
                    <strong><?php echo number_format((float) $stats['total_spent'], 2); ?></strong>
                </div>
                <div>
                    <span>Last purchase</span>
                    <strong><?php echo $stats['last_purchase'] ? e(date('Y-m-d', strtotime($stats['last_purchase']))) : 'Never'; ?></strong>
                </div>
                <div>
                    <span>Address</span>
                    <strong><?php echo e($customer['address'] ?: 'Not set'); ?></strong>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Date</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saleHistory as $sale): ?>
                            <tr>
                                <td><strong><?php echo e($sale['invoice_number']); ?></strong></td>
                                <td><?php echo e(date('Y-m-d H:i', strtotime($sale['sale_date']))); ?></td>
                                <td><?php echo e(str_replace('_', ' ', ucfirst($sale['payment_method']))); ?></td>
                                <td><?php echo number_format((float) $sale['total_amount'], 2); ?></td>
                                <td><?php echo number_format((float) $sale['amount_paid'], 2); ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo url('modules/sales/show.php?id=' . (int) $sale['id']); ?>">Open</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($saleHistory === []): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No purchase history yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

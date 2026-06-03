<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Expiry Report';
$activeModule = 'reports';
$pdo = Database::connection();
$days = max(1, (int) ($_GET['days'] ?? 90));

$statement = $pdo->prepare(
    'SELECT medicine_code, medicine_name, quantity, expiry_date
     FROM medicines
     WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
     ORDER BY expiry_date ASC'
);
$statement->bindValue(':days', $days, PDO::PARAM_INT);
$statement->execute();
$medicines = $statement->fetchAll();

require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel report-panel">
            <div class="panel-header no-print">
                <div>
                    <h2>Expiry Report</h2>
                    <p class="text-muted mb-0">Medicines expired or expiring within <?php echo (int) $days; ?> days.</p>
                </div>
                <div class="action-buttons">
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print / Save PDF</button>
                    <a class="btn btn-light" href="<?php echo url('modules/reports/index.php'); ?>">Back</a>
                </div>
            </div>

            <form class="filter-bar compact no-print" method="get">
                <input class="form-control" type="number" min="1" name="days" value="<?php echo (int) $days; ?>">
                <button class="btn btn-outline-primary" type="submit">Generate</button>
            </form>

            <div class="table-responsive mt-3">
                <table class="table align-middle data-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Medicine</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $medicine): ?>
                            <?php $expired = $medicine['expiry_date'] < date('Y-m-d'); ?>
                            <tr>
                                <td><strong><?php echo e($medicine['medicine_code']); ?></strong></td>
                                <td><?php echo e($medicine['medicine_name']); ?></td>
                                <td><?php echo (int) $medicine['quantity']; ?></td>
                                <td><?php echo e($medicine['expiry_date']); ?></td>
                                <td><span class="badge <?php echo $expired ? 'text-bg-danger' : 'text-bg-warning'; ?>"><?php echo $expired ? 'Expired' : 'Expiring'; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($medicines === []): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No expiry risks found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

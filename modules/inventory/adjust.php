<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Adjust Stock';
$activeModule = 'inventory';
$pdo = Database::connection();
$selectedMedicineId = (int) ($_GET['medicine_id'] ?? old('medicine_id', 0));
$medicines = $pdo->query('SELECT id, medicine_code, medicine_name, quantity FROM medicines ORDER BY medicine_name')->fetchAll();
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
                    <h2>Adjust Stock</h2>
                    <p class="text-muted mb-0">Record stock corrections, damage, expiry, or manual stock additions.</p>
                </div>
                <a class="btn btn-light" href="<?php echo url('modules/inventory/index.php'); ?>">Back to inventory</a>
            </div>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form class="entity-form" method="post" action="<?php echo url('modules/inventory/store_adjustment.php'); ?>" novalidate>
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="medicine_id">Medicine</label>
                        <select class="form-select" id="medicine_id" name="medicine_id" required>
                            <option value="">Select medicine</option>
                            <?php foreach ($medicines as $medicine): ?>
                                <option value="<?php echo (int) $medicine['id']; ?>" <?php echo $selectedMedicineId === (int) $medicine['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($medicine['medicine_name'] . ' (' . $medicine['medicine_code'] . ') - Current ' . $medicine['quantity']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="adjustment_type">Adjustment type</label>
                        <select class="form-select" id="adjustment_type" name="adjustment_type" required>
                            <option value="increase" <?php echo old('adjustment_type') === 'increase' ? 'selected' : ''; ?>>Increase stock</option>
                            <option value="decrease" <?php echo old('adjustment_type') === 'decrease' ? 'selected' : ''; ?>>Decrease stock</option>
                            <option value="expired" <?php echo old('adjustment_type') === 'expired' ? 'selected' : ''; ?>>Mark expired stock removed</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="quantity">Quantity</label>
                        <input class="form-control" id="quantity" name="quantity" type="number" min="1" value="<?php echo e(old('quantity', 1)); ?>" required>
                    </div>
                    <div class="form-grid-full">
                        <label class="form-label" for="reason">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Example: stock count correction, damaged box, expired batch"><?php echo e(old('reason', '')); ?></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <a class="btn btn-light" href="<?php echo url('modules/inventory/index.php'); ?>">Cancel</a>
                    <button class="btn btn-primary" type="submit">Save adjustment</button>
                </div>
            </form>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

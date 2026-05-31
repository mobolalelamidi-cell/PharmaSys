<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'New Purchase';
$activeModule = 'purchases';
$pdo = Database::connection();
$suppliers = $pdo->query('SELECT id, company_name FROM suppliers ORDER BY company_name')->fetchAll();
$medicines = $pdo->query('SELECT id, medicine_code, medicine_name, purchase_price, quantity FROM medicines ORDER BY medicine_name')->fetchAll();
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
                    <h2>Register Purchase</h2>
                    <p class="text-muted mb-0">Add supplier purchase items and update inventory automatically.</p>
                </div>
            </div>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($suppliers === [] || $medicines === []): ?>
                <div class="alert alert-warning">
                    Create at least one supplier and one medicine before registering purchases.
                </div>
            <?php endif; ?>

            <form class="entity-form" method="post" action="<?php echo url('modules/purchases/store.php'); ?>" data-purchase-form novalidate>
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="supplier_id">Supplier</label>
                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                            <option value="">Select supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo (int) $supplier['id']; ?>" <?php echo (string) old('supplier_id') === (string) $supplier['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($supplier['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="purchase_reference">Purchase reference</label>
                        <input class="form-control" id="purchase_reference" name="purchase_reference" value="<?php echo e(old('purchase_reference', '')); ?>" placeholder="Auto-generated if empty">
                    </div>
                </div>

                <div class="purchase-items" data-purchase-items>
                    <div class="purchase-item-row purchase-item-head">
                        <span>Medicine</span>
                        <span>Quantity</span>
                        <span>Unit cost</span>
                        <span>Subtotal</span>
                        <span></span>
                    </div>
                    <div class="purchase-item-row" data-purchase-row>
                        <select class="form-select" name="medicine_id[]" data-medicine-select required>
                            <option value="">Select medicine</option>
                            <?php foreach ($medicines as $medicine): ?>
                                <option value="<?php echo (int) $medicine['id']; ?>" data-price="<?php echo e((string) $medicine['purchase_price']); ?>">
                                    <?php echo e($medicine['medicine_name'] . ' (' . $medicine['medicine_code'] . ') - Stock ' . $medicine['quantity']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input class="form-control" name="quantity[]" type="number" min="1" value="1" data-quantity required>
                        <input class="form-control" name="purchase_price[]" type="number" min="0" step="0.01" value="0.00" data-unit-price required>
                        <input class="form-control" type="text" value="0.00" data-subtotal readonly>
                        <button class="btn btn-outline-danger" type="button" data-remove-row>Remove</button>
                    </div>
                </div>

                <div class="purchase-summary">
                    <button class="btn btn-outline-primary" type="button" data-add-purchase-row>Add item</button>
                    <div class="purchase-total">
                        <span>Total amount</span>
                        <strong data-purchase-total>0.00</strong>
                    </div>
                </div>

                <div class="form-actions">
                    <a class="btn btn-light" href="<?php echo url('modules/purchases/index.php'); ?>">Cancel</a>
                    <button class="btn btn-primary" type="submit" <?php echo $suppliers === [] || $medicines === [] ? 'disabled' : ''; ?>>Save purchase</button>
                </div>
            </form>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

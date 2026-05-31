<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'cashier']);

$pageTitle = 'New Sale';
$activeModule = 'sales';
$pdo = Database::connection();
$customers = $pdo->query('SELECT id, full_name, phone FROM customers ORDER BY full_name')->fetchAll();
$medicines = $pdo->query('SELECT id, medicine_code, medicine_name, selling_price, quantity FROM medicines WHERE quantity > 0 ORDER BY medicine_name')->fetchAll();
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
                    <h2>Process Sale</h2>
                    <p class="text-muted mb-0">Build a cart, process payment, and generate a receipt.</p>
                </div>
            </div>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($medicines === []): ?>
                <div class="alert alert-warning">No medicines with available stock. Add stock before processing sales.</div>
            <?php endif; ?>

            <form class="entity-form" method="post" action="<?php echo url('modules/sales/store.php'); ?>" data-sale-form novalidate>
                <?php echo csrf_field(); ?>
                <div class="form-grid">
                    <div>
                        <div class="label-row">
                            <label class="form-label mb-0" for="customer_id">Customer</label>
                            <a href="<?php echo url('modules/customers/create.php'); ?>">Add customer</a>
                        </div>
                        <select class="form-select" id="customer_id" name="customer_id">
                            <option value="">Walk-in customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo (int) $customer['id']; ?>" <?php echo (string) old('customer_id') === (string) $customer['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($customer['full_name'] . ($customer['phone'] ? ' - ' . $customer['phone'] : '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="payment_method">Payment method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <div class="purchase-items" data-sale-items>
                    <div class="purchase-item-row purchase-item-head">
                        <span>Medicine</span>
                        <span>Quantity</span>
                        <span>Unit price</span>
                        <span>Subtotal</span>
                        <span></span>
                    </div>
                    <div class="purchase-item-row" data-sale-row>
                        <select class="form-select" name="medicine_id[]" data-sale-medicine required>
                            <option value="">Select medicine</option>
                            <?php foreach ($medicines as $medicine): ?>
                                <option value="<?php echo (int) $medicine['id']; ?>" data-price="<?php echo e((string) $medicine['selling_price']); ?>" data-stock="<?php echo (int) $medicine['quantity']; ?>">
                                    <?php echo e($medicine['medicine_name'] . ' (' . $medicine['medicine_code'] . ') - Stock ' . $medicine['quantity']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input class="form-control" name="quantity[]" type="number" min="1" value="1" data-sale-quantity required>
                        <input class="form-control" name="unit_price[]" type="number" min="0" step="0.01" value="0.00" data-sale-unit-price required>
                        <input class="form-control" type="text" value="0.00" data-sale-subtotal readonly>
                        <button class="btn btn-outline-danger" type="button" data-sale-remove-row>Remove</button>
                    </div>
                </div>

                <div class="payment-grid">
                    <button class="btn btn-outline-primary" type="button" data-sale-add-row>Add item</button>
                    <div>
                        <label class="form-label" for="amount_paid">Amount paid</label>
                        <input class="form-control" id="amount_paid" name="amount_paid" type="number" min="0" step="0.01" value="<?php echo e(old('amount_paid', '0.00')); ?>" data-sale-paid required>
                    </div>
                    <div class="purchase-total">
                        <span>Total</span>
                        <strong data-sale-total>0.00</strong>
                    </div>
                    <div class="purchase-total">
                        <span>Change</span>
                        <strong data-sale-change>0.00</strong>
                    </div>
                </div>

                <div class="form-actions">
                    <a class="btn btn-light" href="<?php echo url('modules/sales/index.php'); ?>">Cancel</a>
                    <button class="btn btn-primary" type="submit" <?php echo $medicines === [] ? 'disabled' : ''; ?>>Complete sale</button>
                </div>
            </form>
        </section>
    </section>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>

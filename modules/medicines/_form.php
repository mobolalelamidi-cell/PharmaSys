<?php
$medicine = $medicine ?? [];
$errors = $errors ?? [];
$categories = $categories ?? [];
$suppliers = $suppliers ?? [];
$action = $action ?? '';
$submitLabel = $submitLabel ?? 'Save medicine';

function field_value(array $medicine, string $key, mixed $default = ''): mixed
{
    return old($key, $medicine[$key] ?? $default);
}
?>
<?php if ($errors !== []): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?php echo e($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="entity-form" method="post" action="<?php echo e($action); ?>" novalidate>
    <?php echo csrf_field(); ?>
    <?php if (!empty($medicine['id'])): ?>
        <input type="hidden" name="id" value="<?php echo (int) $medicine['id']; ?>">
    <?php endif; ?>

    <div class="form-grid">
        <div>
            <label class="form-label" for="medicine_code">Medicine code</label>
            <input class="form-control" id="medicine_code" name="medicine_code" value="<?php echo e(field_value($medicine, 'medicine_code')); ?>" required>
        </div>
        <div>
            <label class="form-label" for="medicine_name">Medicine name</label>
            <input class="form-control" id="medicine_name" name="medicine_name" value="<?php echo e(field_value($medicine, 'medicine_name')); ?>" required>
        </div>
        <div>
            <label class="form-label" for="generic_name">Generic name</label>
            <input class="form-control" id="generic_name" name="generic_name" value="<?php echo e(field_value($medicine, 'generic_name')); ?>">
        </div>
        <div>
            <label class="form-label" for="category_id">Category</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="">No category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int) $category['id']; ?>" <?php echo (string) field_value($medicine, 'category_id') === (string) $category['id'] ? 'selected' : ''; ?>>
                        <?php echo e($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="supplier_id">Supplier</label>
            <select class="form-select" id="supplier_id" name="supplier_id">
                <option value="">No supplier</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo (int) $supplier['id']; ?>" <?php echo (string) field_value($medicine, 'supplier_id') === (string) $supplier['id'] ? 'selected' : ''; ?>>
                        <?php echo e($supplier['company_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="purchase_price">Purchase price</label>
            <input class="form-control" id="purchase_price" name="purchase_price" type="number" min="0" step="0.01" value="<?php echo e(field_value($medicine, 'purchase_price', '0.00')); ?>" required>
        </div>
        <div>
            <label class="form-label" for="selling_price">Selling price</label>
            <input class="form-control" id="selling_price" name="selling_price" type="number" min="0" step="0.01" value="<?php echo e(field_value($medicine, 'selling_price', '0.00')); ?>" required>
        </div>
        <div>
            <label class="form-label" for="quantity">Quantity</label>
            <input class="form-control" id="quantity" name="quantity" type="number" min="0" value="<?php echo e(field_value($medicine, 'quantity', 0)); ?>" required>
        </div>
        <div>
            <label class="form-label" for="minimum_stock">Minimum stock</label>
            <input class="form-control" id="minimum_stock" name="minimum_stock" type="number" min="0" value="<?php echo e(field_value($medicine, 'minimum_stock', 10)); ?>" required>
        </div>
        <div>
            <label class="form-label" for="manufacturing_date">Manufacturing date</label>
            <input class="form-control" id="manufacturing_date" name="manufacturing_date" type="date" value="<?php echo e(field_value($medicine, 'manufacturing_date')); ?>">
        </div>
        <div>
            <label class="form-label" for="expiry_date">Expiry date</label>
            <input class="form-control" id="expiry_date" name="expiry_date" type="date" value="<?php echo e(field_value($medicine, 'expiry_date')); ?>">
        </div>
        <div class="form-grid-full">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4"><?php echo e(field_value($medicine, 'description')); ?></textarea>
        </div>
    </div>

    <div class="form-actions">
        <a class="btn btn-light" href="<?php echo url('modules/medicines/index.php'); ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><?php echo e($submitLabel); ?></button>
    </div>
</form>

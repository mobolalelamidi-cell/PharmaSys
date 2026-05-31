<?php
$supplier = $supplier ?? [];
$errors = $errors ?? [];
$action = $action ?? '';
$submitLabel = $submitLabel ?? 'Save supplier';

function supplier_value(array $supplier, string $key): mixed
{
    return old($key, $supplier[$key] ?? '');
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
    <?php if (!empty($supplier['id'])): ?>
        <input type="hidden" name="id" value="<?php echo (int) $supplier['id']; ?>">
    <?php endif; ?>
    <div class="form-grid">
        <div>
            <label class="form-label" for="company_name">Company name</label>
            <input class="form-control" id="company_name" name="company_name" value="<?php echo e(supplier_value($supplier, 'company_name')); ?>" required>
        </div>
        <div>
            <label class="form-label" for="contact_person">Contact person</label>
            <input class="form-control" id="contact_person" name="contact_person" value="<?php echo e(supplier_value($supplier, 'contact_person')); ?>">
        </div>
        <div>
            <label class="form-label" for="phone">Phone</label>
            <input class="form-control" id="phone" name="phone" value="<?php echo e(supplier_value($supplier, 'phone')); ?>">
        </div>
        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="<?php echo e(supplier_value($supplier, 'email')); ?>">
        </div>
        <div class="form-grid-full">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control" id="address" name="address" rows="4"><?php echo e(supplier_value($supplier, 'address')); ?></textarea>
        </div>
    </div>
    <div class="form-actions">
        <a class="btn btn-light" href="<?php echo url('modules/suppliers/index.php'); ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><?php echo e($submitLabel); ?></button>
    </div>
</form>

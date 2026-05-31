<?php
$customer = $customer ?? [];
$errors = $errors ?? [];
$action = $action ?? '';
$submitLabel = $submitLabel ?? 'Save customer';
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
    <?php if (!empty($customer['id'])): ?>
        <input type="hidden" name="id" value="<?php echo (int) $customer['id']; ?>">
    <?php endif; ?>
    <div class="form-grid">
        <div>
            <label class="form-label" for="full_name">Full name</label>
            <input class="form-control" id="full_name" name="full_name" value="<?php echo e(old('full_name', $customer['full_name'] ?? '')); ?>" required>
        </div>
        <div>
            <label class="form-label" for="phone">Phone</label>
            <input class="form-control" id="phone" name="phone" value="<?php echo e(old('phone', $customer['phone'] ?? '')); ?>">
        </div>
        <div class="form-grid-full">
            <label class="form-label" for="address">Address</label>
            <textarea class="form-control" id="address" name="address" rows="4"><?php echo e(old('address', $customer['address'] ?? '')); ?></textarea>
        </div>
    </div>
    <div class="form-actions">
        <a class="btn btn-light" href="<?php echo url('modules/customers/index.php'); ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><?php echo e($submitLabel); ?></button>
    </div>
</form>

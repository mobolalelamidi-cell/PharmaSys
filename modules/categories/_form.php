<?php
$category = $category ?? [];
$errors = $errors ?? [];
$action = $action ?? '';
$submitLabel = $submitLabel ?? 'Save category';
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
    <?php if (!empty($category['id'])): ?>
        <input type="hidden" name="id" value="<?php echo (int) $category['id']; ?>">
    <?php endif; ?>
    <div class="form-grid">
        <div>
            <label class="form-label" for="name">Category name</label>
            <input class="form-control" id="name" name="name" value="<?php echo e(old('name', $category['name'] ?? '')); ?>" required>
        </div>
        <div class="form-grid-full">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4"><?php echo e(old('description', $category['description'] ?? '')); ?></textarea>
        </div>
    </div>
    <div class="form-actions">
        <a class="btn btn-light" href="<?php echo url('modules/categories/index.php'); ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><?php echo e($submitLabel); ?></button>
    </div>
</form>

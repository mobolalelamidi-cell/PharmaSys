<?php
$success = flash('success');
$error = flash('error');
?>
<?php if ($success || $error): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast show align-items-center <?php echo $success ? 'text-bg-success' : 'text-bg-danger'; ?>" role="alert">
            <div class="d-flex">
                <div class="toast-body"><?php echo e($success ?? $error); ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php $user = current_user(); ?>
<header class="topbar">
    <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
        Menu
    </button>
    <div>
        <p class="topbar-eyebrow mb-0">Pharmacy Management</p>
        <h1 class="topbar-title mb-0"><?php echo e($pageTitle ?? app_config('name')); ?></h1>
    </div>
    <div class="topbar-user ms-auto">
        <span class="user-avatar"><?php echo e(strtoupper(substr($user['full_name'] ?? 'U', 0, 1))); ?></span>
        <div class="d-none d-sm-block">
            <strong><?php echo e($user['full_name'] ?? 'Guest'); ?></strong>
            <small><?php echo e(ucfirst($user['role'] ?? 'visitor')); ?></small>
        </div>
    </div>
</header>

<?php
$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'modules/dashboard/index.php', 'roles' => ['admin', 'pharmacist', 'cashier']],
    ['key' => 'medicines', 'label' => 'Medicines', 'href' => 'modules/medicines/index.php', 'roles' => ['admin', 'pharmacist']],
    ['key' => 'categories', 'label' => 'Categories', 'href' => 'modules/categories/index.php', 'roles' => ['admin', 'pharmacist']],
    ['key' => 'inventory', 'label' => 'Inventory', 'href' => 'modules/inventory/index.php', 'roles' => ['admin', 'pharmacist']],
    ['key' => 'purchases', 'label' => 'Purchases', 'href' => 'modules/purchases/index.php', 'roles' => ['admin', 'pharmacist']],
    ['key' => 'sales', 'label' => 'Sales', 'href' => 'modules/sales/index.php', 'roles' => ['admin', 'cashier']],
    ['key' => 'customers', 'label' => 'Customers', 'href' => 'modules/customers/index.php', 'roles' => ['admin', 'cashier']],
    ['key' => 'suppliers', 'label' => 'Suppliers', 'href' => 'modules/suppliers/index.php', 'roles' => ['admin', 'pharmacist']],
    ['key' => 'prescriptions', 'label' => 'Prescriptions', 'href' => 'modules/prescriptions/index.php', 'roles' => ['admin', 'pharmacist']],
    ['key' => 'expenses', 'label' => 'Expenses', 'href' => 'modules/expenses/index.php', 'roles' => ['admin']],
    ['key' => 'reports', 'label' => 'Reports', 'href' => 'modules/reports/index.php', 'roles' => ['admin', 'pharmacist']],
    ['key' => 'audit', 'label' => 'Audit Logs', 'href' => 'modules/audit/index.php', 'roles' => ['admin']],
];
?>
<aside class="sidebar d-none d-lg-flex">
    <a class="brand" href="<?php echo url('modules/dashboard/index.php'); ?>">
        <span class="brand-mark">P</span>
        <span>PharmaSys</span>
    </a>
    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
            <?php if (user_has_role($item['roles'])): ?>
                <a class="<?php echo $activeModule === $item['key'] ? 'active' : ''; ?>" href="<?php echo url($item['href']); ?>">
                    <?php echo e($item['label']); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <a class="logout-link" href="<?php echo url('modules/auth/logout.php'); ?>">Logout</a>
</aside>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">PharmaSys</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="mobile-nav">
            <?php foreach ($navItems as $item): ?>
                <?php if (user_has_role($item['roles'])): ?>
                    <a class="<?php echo $activeModule === $item['key'] ? 'active' : ''; ?>" href="<?php echo url($item['href']); ?>">
                        <?php echo e($item['label']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?php echo url('modules/auth/logout.php'); ?>">Logout</a>
        </nav>
    </div>
</div>

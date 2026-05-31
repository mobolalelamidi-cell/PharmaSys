<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';

if (current_user() !== null) {
    redirect('modules/dashboard/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if ($errors === []) {
        $statement = Database::connection()->prepare(
            'SELECT id, full_name, email, username, password, role, status FROM users WHERE username = :username OR email = :email LIMIT 1'
        );
        $statement->execute([
            'username' => $username,
            'email' => $username,
        ]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid username or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'Your account is inactive. Contact the administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'username' => $user['username'],
                'role' => $user['role'],
            ];
            clear_old();
            flash('success', 'Welcome back, ' . $user['full_name'] . '.');
            redirect('modules/dashboard/index.php');
        }
    }

    set_old(['username' => $username]);
}

$pageTitle = 'Login - PharmaSys';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-brand">
            <span class="brand-mark">P</span>
            <div>
                <h1>PharmaSys</h1>
                <p>Secure pharmacy operations</p>
            </div>
        </div>

        <?php if ($errors !== []): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label" for="username">Username or email</label>
                <input class="form-control" id="username" name="username" value="<?php echo e(old('username')); ?>" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" name="password" type="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Sign in</button>
        </form>
    </main>
</body>
</html>

<?php require_once __DIR__ . '/../../app/Core/bootstrap.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access denied - PharmaSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body class="auth-page">
    <main class="auth-card text-center">
        <h1>403</h1>
        <p>You do not have permission to access this page.</p>
        <a class="btn btn-primary" href="<?php echo url('modules/dashboard/index.php'); ?>">Back to dashboard</a>
    </main>
</body>
</html>

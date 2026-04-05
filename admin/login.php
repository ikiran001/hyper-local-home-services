<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');

    if ($user === ADMIN_USERNAME && $pass === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php', true, 302);
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin login — Home Services</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="page-wrap">
    <main>
      <div class="container login-box">
        <h1 style="text-align:center;">Admin login</h1>
        <p class="lead" style="text-align:center;">Home Services Booking Platform</p>

        <div class="card-panel">
          <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
          <?php endif; ?>

          <form method="post" action="login.php" autocomplete="off">
            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" required maxlength="100">
            </div>
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required maxlength="200">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign in</button>
          </form>
          <p class="muted" style="margin-top:1rem;text-align:center;">MVP: credentials are set in <code>config/db.php</code></p>
        </div>
      </div>
    </main>
  </div>
</body>
</html>

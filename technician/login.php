<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['technician_id'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/\D/', '', (string) ($_POST['phone'] ?? ''));

    if (strlen($phone) !== 10) {
        $error = 'Enter a valid 10-digit mobile number.';
    } else {
        $conn = db();
        $sql = 'SELECT id, name, phone, service_type, area FROM technicians WHERE phone = ? LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $_SESSION['technician_id'] = (int) $row['id'];
            $_SESSION['technician_name'] = $row['name'];
            header('Location: dashboard.php', true, 302);
            exit;
        }
        $error = 'No technician found for this number.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Technician login — Dispatch</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="page-wrap">
    <main>
      <div class="container login-box">
        <h1 style="text-align:center;">Technician login</h1>
        <p class="lead" style="text-align:center;">Enter your registered mobile number.</p>

        <div class="card-panel">
          <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
          <?php endif; ?>

          <form id="tech-login-form" method="post" action="login.php" novalidate>
            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="tel" id="phone" name="phone" required inputmode="numeric" maxlength="10" placeholder="10-digit mobile" autocomplete="tel">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Continue</button>
          </form>
        </div>
      </div>
    </main>
  </div>
  <script src="../assets/script.js" defer></script>
</body>
</html>

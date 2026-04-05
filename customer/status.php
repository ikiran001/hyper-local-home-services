<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/** Maps DB service keys to labels for display. */
const SERVICE_LABELS = [
    'electrician' => 'Electrician',
    'ac_repair'     => 'AC Repair',
    'plumber'       => 'Plumber',
];

$errors = [];
$bookings = [];
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = preg_replace('/\D/', '', (string) ($_POST['phone'] ?? ''));
    if (strlen($phone) !== 10) {
        $errors[] = 'Please enter a valid 10-digit mobile number.';
    } else {
        $conn = db();
        $sql = 'SELECT id, name, phone, address, service, issue, status, created_at
                FROM bookings WHERE phone = ? ORDER BY created_at DESC';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$phone]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Booking status — Home Services</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="page-wrap">
    <header class="site-header">
      <div class="container inner">
        <a class="logo" href="index.php">Home Services</a>
        <nav class="nav-links">
          <a href="index.php">Services</a>
          <a href="book.php">Book</a>
          <a href="status.php">Booking status</a>
        </nav>
      </div>
    </header>

    <main>
      <div class="container">
        <h1>Booking status</h1>
        <p class="lead">Enter the phone number you used while booking.</p>

        <div class="card-panel" style="max-width: 420px;">
          <?php if ($errors !== []): ?>
            <div class="alert alert-error" role="alert">
              <?php foreach ($errors as $e): ?>
                <div><?php echo e($e); ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form id="status-form" method="post" action="status.php" novalidate>
            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="tel" id="phone" name="phone" required inputmode="numeric" maxlength="10" placeholder="10-digit mobile" value="<?php echo e($phone); ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-block">View my bookings</button>
          </form>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors === []): ?>
          <h2 class="section-title">Your bookings</h2>
          <?php if ($bookings === []): ?>
            <p class="empty-state">No bookings found for this number.</p>
          <?php else: ?>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Booked on</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bookings as $b): ?>
                    <?php
                    $svc = SERVICE_LABELS[$b['service']] ?? $b['service'];
                    $st = $b['status'];
                    $badge = 'badge-' . $st;
                    ?>
                    <tr>
                      <td><?php echo e($svc); ?></td>
                      <td><span class="badge <?php echo e($badge); ?>"><?php echo e($st); ?></span></td>
                      <td class="nowrap"><?php echo e(date('d M Y, h:i A', strtotime($b['created_at']))); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Home Services Booking Platform</p>
      </div>
    </footer>
  </div>
  <script src="../assets/script.js" defer></script>
</body>
</html>

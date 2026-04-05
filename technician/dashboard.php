<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

const SERVICE_LABELS = [
    'electrician' => 'Electrician',
    'ac_repair'     => 'AC Repair',
    'plumber'       => 'Plumber',
];

$techId = (int) $_SESSION['technician_id'];
$techName = (string) ($_SESSION['technician_name'] ?? 'Technician');
$flash = '';

// Mark job completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete') {
    $bid = (int) ($_POST['booking_id'] ?? 0);
    if ($bid > 0) {
        $conn = db();
        $sql = 'UPDATE bookings SET status = "completed" WHERE id = ? AND technician_id = ? AND status = "assigned"';
        $stmt = $conn->prepare($sql);
        $stmt->execute([$bid, $techId]);
        if ($stmt->rowCount() > 0) {
            $flash = 'Job marked as completed.';
        }
    }
    header('Location: dashboard.php?msg=' . rawurlencode($flash), true, 302);
    exit;
}

if (isset($_GET['msg']) && is_string($_GET['msg'])) {
    $flash = $_GET['msg'];
}

$conn = db();
$sql = 'SELECT id, name, phone, address, service, issue, status, created_at
        FROM bookings
        WHERE technician_id = ?
        ORDER BY
          CASE WHEN status = "assigned" THEN 0 ELSE 1 END,
          created_at DESC';
$stmt = $conn->prepare($sql);
$stmt->execute([$techId]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$active = array_filter($jobs, static fn ($j) => $j['status'] === 'assigned');
$done = array_filter($jobs, static fn ($j) => $j['status'] === 'completed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Technician dashboard — Home Services</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="page-wrap">
    <header class="site-header">
      <div class="container inner">
        <span class="logo"><?php echo e($techName); ?></span>
        <nav class="nav-links">
          <a href="dashboard.php">Jobs</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main>
      <div class="container">
        <div class="page-header-row">
          <h1>Your jobs</h1>
        </div>

        <?php if ($flash !== ''): ?>
          <div class="alert alert-success" role="status"><?php echo e($flash); ?></div>
        <?php endif; ?>

        <h2 class="section-title">Assigned (active)</h2>
        <?php if ($active === []): ?>
          <p class="empty-state">No active assignments right now.</p>
        <?php else: ?>
          <div class="job-list">
            <?php foreach ($active as $j): ?>
              <article class="job-card">
                <dl>
                  <dt>Service</dt>
                  <dd><?php echo e(SERVICE_LABELS[$j['service']] ?? $j['service']); ?></dd>
                  <dt>Customer</dt>
                  <dd><?php echo e($j['name']); ?></dd>
                  <dt>Phone</dt>
                  <dd><a href="tel:<?php echo e($j['phone']); ?>"><?php echo e($j['phone']); ?></a></dd>
                  <dt>Address</dt>
                  <dd><?php echo nl2br(e($j['address'])); ?></dd>
                  <dt>Issue</dt>
                  <dd><?php echo nl2br(e($j['issue'])); ?></dd>
                  <dt>Booked</dt>
                  <dd><?php echo e(date('d M Y, h:i A', strtotime($j['created_at']))); ?></dd>
                </dl>
                <form method="post" action="dashboard.php" style="margin-top:1rem;">
                  <input type="hidden" name="action" value="complete">
                  <input type="hidden" name="booking_id" value="<?php echo (int) $j['id']; ?>">
                  <button type="submit" class="btn btn-primary">Mark as completed</button>
                </form>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <h2 class="section-title">Completed</h2>
        <?php if ($done === []): ?>
          <p class="empty-state">No completed jobs yet.</p>
        <?php else: ?>
          <div class="job-list">
            <?php foreach ($done as $j): ?>
              <article class="job-card">
                <dl>
                  <dt>Service</dt>
                  <dd><?php echo e(SERVICE_LABELS[$j['service']] ?? $j['service']); ?></dd>
                  <dt>Customer</dt>
                  <dd><?php echo e($j['name']); ?></dd>
                  <dt>Phone</dt>
                  <dd><?php echo e($j['phone']); ?></dd>
                  <dt>Address</dt>
                  <dd><?php echo nl2br(e($j['address'])); ?></dd>
                  <dt>Issue</dt>
                  <dd><?php echo nl2br(e($j['issue'])); ?></dd>
                </dl>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <p>Technician portal — Home Services</p>
      </div>
    </footer>
  </div>
</body>
</html>

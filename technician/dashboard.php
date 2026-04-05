<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$techId = (int) $_SESSION['technician_id'];
$techName = (string) ($_SESSION['technician_name'] ?? 'Technician');
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete') {
    $bid = (int) ($_POST['booking_id'] ?? 0);
    if ($bid > 0) {
        $conn = db();
        $conn->begin_transaction();
        try {
            $st = $conn->prepare('UPDATE bookings SET status = "completed", completed_at = NOW() WHERE id = ? AND technician_id = ? AND status = "assigned"');
            $st->bind_param('ii', $bid, $techId);
            $st->execute();
            $affected = $st->affected_rows;
            $st->close();

            if ($affected > 0) {
                $f = $conn->prepare('UPDATE technicians SET is_available = 1 WHERE id = ?');
                $f->bind_param('i', $techId);
                $f->execute();
                $f->close();
                $conn->commit();
                $flash = 'Job marked completed. You are available for new jobs.';
            } else {
                $conn->rollback();
            }
        } catch (Throwable $e) {
            $conn->rollback();
            $flash = 'Could not complete job.';
        }
    }
    header('Location: dashboard.php?msg=' . rawurlencode($flash), true, 302);
    exit;
}

if (isset($_GET['msg']) && is_string($_GET['msg'])) {
    $flash = $_GET['msg'];
}

$conn = db();
$sql = 'SELECT id, name, phone, address, service, issue, priority, area, created_at
        FROM bookings
        WHERE technician_id = ? AND status = "assigned"
        ORDER BY CASE priority WHEN "urgent" THEN 0 ELSE 1 END, created_at DESC';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $techId);
$stmt->execute();
$res = $stmt->get_result();
$jobs = [];
while ($row = $res->fetch_assoc()) {
    $jobs[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Technician — Dispatch</title>
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
          <h1>Assigned jobs</h1>
        </div>

        <?php if ($flash !== ''): ?>
          <div class="alert alert-success" role="status"><?php echo e($flash); ?></div>
        <?php endif; ?>

        <?php if ($jobs === []): ?>
          <p class="empty-state">No active assignments.</p>
        <?php else: ?>
          <div class="job-list">
            <?php foreach ($jobs as $j): ?>
              <?php $urgent = $j['priority'] === 'urgent'; ?>
              <article class="job-card <?php echo $urgent ? 'job-urgent' : ''; ?>">
                <dl>
                  <dt>Service</dt>
                  <dd><?php echo e(label_service($j['service'])); ?></dd>
                  <dt>Area</dt>
                  <dd><?php echo e(label_area($j['area'])); ?></dd>
                  <dt>Priority</dt>
                  <dd>
                    <?php if ($urgent): ?>
                      <span class="badge badge-urgent">Urgent</span>
                    <?php else: ?>
                      <span class="badge">Normal</span>
                    <?php endif; ?>
                  </dd>
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
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <p>Technician — Home Services Dispatch System</p>
      </div>
    </footer>
  </div>
</body>
</html>

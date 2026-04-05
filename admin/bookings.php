<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

const SERVICE_LABELS = [
    'electrician' => 'Electrician',
    'ac_repair'     => 'AC Repair',
    'plumber'       => 'Plumber',
];

$allowedStatus = ['pending', 'assigned', 'completed'];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $conn = db();

    if ($action === 'assign') {
        $bid = (int) ($_POST['booking_id'] ?? 0);
        $tid = (int) ($_POST['technician_id'] ?? 0);

        if ($bid > 0 && $tid > 0) {
            // Verify booking exists and technician matches service type
            $q = 'SELECT b.service FROM bookings b WHERE b.id = ?';
            $st = $conn->prepare($q);
            $st->execute([$bid]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $q2 = 'SELECT id FROM technicians WHERE id = ? AND service_type = ?';
                $st2 = $conn->prepare($q2);
                $st2->execute([$tid, $row['service']]);
                $ok = $st2->fetch(PDO::FETCH_ASSOC);
            } else {
                $ok = null;
            }

            if ($row && $ok) {
                $up = $conn->prepare('UPDATE bookings SET technician_id = ?, status = "assigned" WHERE id = ?');
                $up->execute([$tid, $bid]);
                $flash = 'Technician assigned and status set to Assigned.';
            } else {
                $flash = 'Invalid assignment.';
            }
        }
    } elseif ($action === 'status') {
        $bid = (int) ($_POST['booking_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if ($bid > 0 && in_array($status, $allowedStatus, true)) {
            $up = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $up->execute([$status, $bid]);
            $flash = 'Status updated.';
        }
    }

    header('Location: bookings.php?msg=' . rawurlencode($flash), true, 302);
    exit;
}

if (isset($_GET['msg']) && is_string($_GET['msg'])) {
    $flash = $_GET['msg'];
}

$conn = db();
$sql = 'SELECT b.id, b.name, b.phone, b.address, b.service, b.issue, b.status, b.technician_id, b.created_at,
               t.name AS technician_name
        FROM bookings b
        LEFT JOIN technicians t ON b.technician_id = t.id
        ORDER BY b.created_at DESC';
$rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Technicians grouped by service for dropdowns
$techByService = [];
$tr = $conn->query('SELECT id, name, phone, service_type, area FROM technicians WHERE is_available = 1 ORDER BY name');
foreach ($tr->fetchAll(PDO::FETCH_ASSOC) as $t) {
    $techByService[$t['service_type']][] = $t;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings — Admin</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="page-wrap">
    <header class="site-header">
      <div class="container inner">
        <span class="logo">Admin</span>
        <nav class="nav-links">
          <a href="dashboard.php">Dashboard</a>
          <a href="bookings.php">Bookings</a>
          <a href="technicians.php">Technicians</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>
    </header>

    <main>
      <div class="container">
        <div class="page-header-row">
          <h1>Booking management</h1>
        </div>

        <?php if ($flash !== ''): ?>
          <div class="alert alert-success" role="status"><?php echo e($flash); ?></div>
        <?php endif; ?>

        <?php if ($rows === []): ?>
          <p class="empty-state">No bookings yet.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Customer</th>
                  <th>Phone</th>
                  <th>Service</th>
                  <th>Issue</th>
                  <th>Status</th>
                  <th>Technician</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $b): ?>
                  <?php
                  $svcLabel = SERVICE_LABELS[$b['service']] ?? $b['service'];
                  $badge = 'badge-' . $b['status'];
                  $techs = $techByService[$b['service']] ?? [];
                  ?>
                  <tr>
                    <td><?php echo e($b['name']); ?></td>
                    <td class="nowrap"><?php echo e($b['phone']); ?></td>
                    <td><?php echo e($svcLabel); ?></td>
                    <td><?php echo nl2br(e($b['issue'])); ?></td>
                    <td><span class="badge <?php echo e($badge); ?>"><?php echo e($b['status']); ?></span></td>
                    <td><?php echo e($b['technician_name'] ?? '—'); ?></td>
                    <td>
                      <?php if ($techs === []): ?>
                        <p class="muted" style="margin:0 0 0.5rem;font-size:0.8rem;">No technicians for this service. Add one under Technicians.</p>
                      <?php else: ?>
                      <form method="post" action="bookings.php" style="margin-bottom:0.5rem;">
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                        <select name="technician_id" required style="max-width:180px;font-size:0.8rem;">
                          <option value="">Assign technician…</option>
                          <?php foreach ($techs as $tech): ?>
                            <option value="<?php echo (int) $tech['id']; ?>" <?php echo (int) $b['technician_id'] === (int) $tech['id'] ? 'selected' : ''; ?>>
                              <?php echo e($tech['name'] . ' — ' . $tech['area']); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                      </form>
                      <?php endif; ?>
                      <form method="post" action="bookings.php">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                        <select name="status" style="max-width:140px;font-size:0.8rem;">
                          <?php foreach ($allowedStatus as $s): ?>
                            <option value="<?php echo e($s); ?>" <?php echo $b['status'] === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Update status</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <p>Admin — Bookings</p>
      </div>
    </footer>
  </div>
</body>
</html>

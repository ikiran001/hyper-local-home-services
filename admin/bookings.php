<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$allowedStatus = ['pending', 'assigned', 'completed'];
$flash = '';
$waOpen = null; // ['phone' => '10digit', 'message' => '...']

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $conn = db();

    if ($action === 'assign') {
        $bid = (int) ($_POST['booking_id'] ?? 0);
        $tid = (int) ($_POST['technician_id'] ?? 0);

        if ($bid > 0 && $tid > 0) {
            $conn->begin_transaction();
            try {
                $st = $conn->prepare('SELECT id, service, area, technician_id, name, phone, address, issue, priority FROM bookings WHERE id = ?');
                $st->bind_param('i', $bid);
                $st->execute();
                $b = $st->get_result()->fetch_assoc();
                $st->close();

                $techRow = null;
                if ($b) {
                    $chk = $conn->prepare('SELECT id, phone FROM technicians WHERE id = ? AND service_type = ? AND area = ? AND (is_available = 1 OR id = ?)');
                    $cur = (int) ($b['technician_id'] ?? 0);
                    $chk->bind_param('issi', $tid, $b['service'], $b['area'], $cur);
                    $chk->execute();
                    $techRow = $chk->get_result()->fetch_assoc();
                    $chk->close();
                }

                if ($b && $techRow) {
                    $oldTid = (int) ($b['technician_id'] ?? 0);
                    if ($oldTid > 0 && $oldTid !== $tid) {
                        $f = $conn->prepare('UPDATE technicians SET is_available = 1 WHERE id = ?');
                        $f->bind_param('i', $oldTid);
                        $f->execute();
                        $f->close();
                    }

                    $up = $conn->prepare('UPDATE bookings SET technician_id = ?, status = "assigned", completed_at = NULL WHERE id = ?');
                    $up->bind_param('ii', $tid, $bid);
                    $up->execute();
                    $up->close();

                    $busy = $conn->prepare('UPDATE technicians SET is_available = 0 WHERE id = ?');
                    $busy->bind_param('i', $tid);
                    $busy->execute();
                    $busy->close();

                    $conn->commit();
                    $flash = 'Technician assigned.';

                    $pLabel = $b['priority'] === 'urgent' ? 'Urgent' : 'Normal';
                    $areaLabel = label_area($b['area']);
                    $svcLabel = label_service($b['service']);
                    $msg = sprintf(
                        'New Job Assigned: %s | Customer: %s | Phone: %s | Area: %s | Address: %s | Issue: %s | Priority: %s',
                        $svcLabel,
                        $b['name'],
                        $b['phone'],
                        $areaLabel,
                        $b['address'],
                        $b['issue'],
                        $pLabel
                    );
                    $waOpen = ['phone' => preg_replace('/\D/', '', $techRow['phone']), 'message' => $msg];
                } else {
                    $conn->rollback();
                    $flash = 'Invalid assignment — technician must match service, area, and availability (or current assignee).';
                }
            } catch (Throwable $e) {
                $conn->rollback();
                $flash = 'Assignment failed. Please try again.';
            }
        }
    } elseif ($action === 'status') {
        $bid = (int) ($_POST['booking_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if ($bid > 0 && in_array($status, $allowedStatus, true)) {
            $st = $conn->prepare('SELECT technician_id, status AS st FROM bookings WHERE id = ?');
            $st->bind_param('i', $bid);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();

            if ($row) {
                if ($status === 'assigned' && empty($row['technician_id'])) {
                    $flash = 'Cannot set Assigned without a technician — use Assign first.';
                } else {
                    $conn->begin_transaction();
                    try {
                        $oldTid = (int) ($row['technician_id'] ?? 0);

                        if ($status === 'pending') {
                            if ($oldTid > 0) {
                                $f = $conn->prepare('UPDATE technicians SET is_available = 1 WHERE id = ?');
                                $f->bind_param('i', $oldTid);
                                $f->execute();
                                $f->close();
                            }
                            $up = $conn->prepare('UPDATE bookings SET status = "pending", technician_id = NULL, completed_at = NULL WHERE id = ?');
                            $up->bind_param('i', $bid);
                            $up->execute();
                            $up->close();
                        } elseif ($status === 'completed') {
                            if ($oldTid > 0) {
                                $f = $conn->prepare('UPDATE technicians SET is_available = 1 WHERE id = ?');
                                $f->bind_param('i', $oldTid);
                                $f->execute();
                                $f->close();
                            }
                            $up = $conn->prepare('UPDATE bookings SET status = "completed", completed_at = NOW() WHERE id = ?');
                            $up->bind_param('i', $bid);
                            $up->execute();
                            $up->close();
                        } elseif ($status === 'assigned') {
                            $up = $conn->prepare('UPDATE bookings SET status = "assigned", completed_at = NULL WHERE id = ?');
                            $up->bind_param('i', $bid);
                            $up->execute();
                            $up->close();
                        }

                        $conn->commit();
                        $flash = 'Status updated.';
                    } catch (Throwable $e) {
                        $conn->rollback();
                        $flash = 'Status update failed.';
                    }
                }
            }
        }
    } elseif ($action === 'price') {
        $bid = (int) ($_POST['booking_id'] ?? 0);
        $priceRaw = trim((string) ($_POST['price'] ?? '0'));
        $price = (float) $priceRaw;
        if ($bid > 0 && $price >= 0) {
            $st = $conn->prepare('UPDATE bookings SET price = ? WHERE id = ? AND status = "completed"');
            $st->bind_param('di', $price, $bid);
            $st->execute();
            $st->close();
            $flash = 'Price saved.';
        }
    }

    if ($waOpen !== null) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['wa_tech_notify'] = $waOpen;
    }

    header('Location: bookings.php?msg=' . rawurlencode($flash), true, 302);
    exit;
}

if (isset($_GET['msg']) && is_string($_GET['msg'])) {
    $flash = $_GET['msg'];
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!empty($_SESSION['wa_tech_notify'])) {
    $waOpen = $_SESSION['wa_tech_notify'];
    unset($_SESSION['wa_tech_notify']);
}

$conn = db();
$sql = 'SELECT b.id, b.name, b.phone, b.address, b.area, b.service, b.issue, b.priority, b.status, b.technician_id, b.price, b.created_at,
               t.name AS technician_name
        FROM bookings b
        LEFT JOIN technicians t ON b.technician_id = t.id
        ORDER BY
          CASE b.priority WHEN "urgent" THEN 0 ELSE 1 END,
          b.created_at DESC';
$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

// Technicians per booking (service + area + available OR current assignee)
$techByBooking = [];
foreach ($rows as $b) {
    $cur = (int) ($b['technician_id'] ?? 0);
    $q = 'SELECT id, name, phone, area FROM technicians
          WHERE service_type = ? AND area = ? AND (is_available = 1 OR id = ?)
          ORDER BY name';
    $st = $conn->prepare($q);
    $svc = $b['service'];
    $area = $b['area'];
    $st->bind_param('ssi', $svc, $area, $cur);
    $st->execute();
    $tr = $st->get_result();
    $techByBooking[(int) $b['id']] = [];
    while ($t = $tr->fetch_assoc()) {
        $techByBooking[(int) $b['id']][] = $t;
    }
    $st->close();
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
                  <th>Area</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Tech</th>
                  <th>Price (₹)</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $b): ?>
                  <?php
                  $urgent = $b['priority'] === 'urgent';
                  $rowClass = $urgent ? 'row-urgent' : '';
                  $badge = 'badge-' . $b['status'];
                  $techs = $techByBooking[(int) $b['id']] ?? [];
                  ?>
                  <tr class="<?php echo e($rowClass); ?>">
                    <td><?php echo e($b['name']); ?></td>
                    <td class="nowrap"><?php echo e($b['phone']); ?></td>
                    <td><?php echo e(label_service($b['service'])); ?></td>
                    <td><?php echo e(label_area($b['area'])); ?></td>
                    <td>
                      <?php if ($urgent): ?>
                        <span class="badge badge-urgent">Urgent</span>
                      <?php else: ?>
                        <span class="badge">Normal</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo e($badge); ?>"><?php echo e($b['status']); ?></span></td>
                    <td><?php echo e($b['technician_name'] ?? '—'); ?></td>
                    <td>
                      <?php if ($b['status'] === 'completed'): ?>
                        <form method="post" action="bookings.php" class="inline-price">
                          <input type="hidden" name="action" value="price">
                          <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                          <input type="number" name="price" step="0.01" min="0" class="input-price" value="<?php echo e((string) $b['price']); ?>" required>
                          <button type="submit" class="btn btn-primary btn-sm">Save</button>
                        </form>
                      <?php else: ?>
                        <?php echo e(number_format((float) $b['price'], 2)); ?>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($techs === []): ?>
                        <p class="muted small">No matching technicians (service + area + available).</p>
                      <?php else: ?>
                      <form method="post" action="bookings.php" class="action-form">
                        <input type="hidden" name="action" value="assign">
                        <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                        <select name="technician_id" required>
                          <option value="">Assign…</option>
                          <?php foreach ($techs as $tech): ?>
                            <option value="<?php echo (int) $tech['id']; ?>" <?php echo (int) $b['technician_id'] === (int) $tech['id'] ? 'selected' : ''; ?>>
                              <?php echo e($tech['name'] . ' — ' . label_area($tech['area'])); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Assign</button>
                      </form>
                      <?php endif; ?>
                      <form method="post" action="bookings.php" class="action-form">
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="booking_id" value="<?php echo (int) $b['id']; ?>">
                        <select name="status">
                          <?php foreach ($allowedStatus as $s): ?>
                            <option value="<?php echo e($s); ?>" <?php echo $b['status'] === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Update</button>
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

  <?php if ($waOpen !== null): ?>
  <script>
  (function () {
    var phone = <?php echo json_encode($waOpen['phone'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var msg = <?php echo json_encode($waOpen['message'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    if (phone && phone.length >= 10) {
      var url = 'https://wa.me/91' + phone.replace(/\D/g, '') + '?text=' + encodeURIComponent(msg);
      window.open(url, '_blank');
    }
  })();
  </script>
  <?php endif; ?>
</body>
</html>

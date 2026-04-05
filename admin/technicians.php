<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$services = service_options();
$areas = area_options();
$errors = [];
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = preg_replace('/\D/', '', (string) ($_POST['phone'] ?? ''));
    $serviceType = (string) ($_POST['service_type'] ?? '');
    $area = (string) ($_POST['area'] ?? '');
    $availability = (string) ($_POST['availability'] ?? 'available');
    $isAvailable = $availability === 'available' ? 1 : 0;

    if (strlen($name) < 2) {
        $errors[] = 'Enter the technician name.';
    }
    if (strlen($phone) !== 10) {
        $errors[] = 'Enter a valid 10-digit phone number.';
    }
    if (!isset($services[$serviceType])) {
        $errors[] = 'Select a valid service type.';
    }
    if (!isset($areas[$area])) {
        $errors[] = 'Select a valid area.';
    }

    if ($errors === []) {
        $conn = db();
        $sql = 'INSERT INTO technicians (name, phone, service_type, area, is_available) VALUES (?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssi', $name, $phone, $serviceType, $area, $isAvailable);
        $stmt->execute();
        $stmt->close();
        header('Location: technicians.php?added=1', true, 302);
        exit;
    }
}

if (isset($_GET['added'])) {
    $flash = 'Technician added.';
}

$conn = db();
$list = [];
$res = $conn->query('SELECT id, name, phone, service_type, area, is_available, created_at FROM technicians ORDER BY created_at DESC');
while ($row = $res->fetch_assoc()) {
    $list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Technicians — Admin</title>
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
          <h1>Technician management</h1>
        </div>

        <?php if ($flash !== ''): ?>
          <div class="alert alert-success" role="status"><?php echo e($flash); ?></div>
        <?php endif; ?>

        <h2 class="section-title">Add technician</h2>
        <div class="card-panel" style="max-width: 560px; margin: 0 0 2rem;">
          <?php if ($errors !== []): ?>
            <div class="alert alert-error" role="alert">
              <?php foreach ($errors as $e): ?>
                <div><?php echo e($e); ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="post" action="technicians.php">
            <div class="form-group">
              <label for="name">Name</label>
              <input type="text" id="name" name="name" required maxlength="255" value="<?php echo e($_POST['name'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="tel" id="phone" name="phone" required inputmode="numeric" maxlength="10" placeholder="10-digit mobile" value="<?php echo e($_POST['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="service_type">Service type</label>
              <select id="service_type" name="service_type" required>
                <option value="">— Select —</option>
                <?php foreach ($services as $val => $label): ?>
                  <option value="<?php echo e($val); ?>" <?php echo (isset($_POST['service_type']) && $_POST['service_type'] === $val) ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="area">Area</label>
              <select id="area" name="area" required>
                <option value="">— Select —</option>
                <?php foreach ($areas as $val => $label): ?>
                  <option value="<?php echo e($val); ?>" <?php echo (isset($_POST['area']) && $_POST['area'] === $val) ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="availability">Availability</label>
              <select id="availability" name="availability" required>
                <option value="available" <?php echo (!isset($_POST['availability']) || $_POST['availability'] === 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="busy" <?php echo (isset($_POST['availability']) && $_POST['availability'] === 'busy') ? 'selected' : ''; ?>>Busy</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary">Add technician</button>
          </form>
        </div>

        <h2 class="section-title">All technicians</h2>
        <?php if ($list === []): ?>
          <p class="empty-state">No technicians yet.</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Phone</th>
                  <th>Service</th>
                  <th>Area</th>
                  <th>Availability</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($list as $t): ?>
                  <tr>
                    <td><?php echo e($t['name']); ?></td>
                    <td><?php echo e($t['phone']); ?></td>
                    <td><?php echo e($services[$t['service_type']] ?? $t['service_type']); ?></td>
                    <td><?php echo e(label_area($t['area'])); ?></td>
                    <td><?php echo (int) $t['is_available'] === 1 ? 'Available' : 'Busy'; ?></td>
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
        <p>Admin — Technicians</p>
      </div>
    </footer>
  </div>
</body>
</html>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$services = service_options();
$areas = area_options();
$priorities = priority_options();

$errors = [];
$old = [
    'name'     => '',
    'phone'    => '',
    'address'  => '',
    'area'     => array_key_first($areas) ?: 'vikhroli',
    'service'  => 'electrician',
    'issue'    => '',
    'priority' => 'normal',
];

if (isset($_GET['service']) && is_string($_GET['service']) && isset($services[$_GET['service']])) {
    $old['service'] = $_GET['service'];
}

/**
 * @return array{0: array<int, string>, 1: array<string, string>}
 */
function validate_booking(array $p, array $services, array $areas, array $priorities): array
{
    $err = [];
    $name = trim((string) ($p['name'] ?? ''));
    $phone = preg_replace('/\D/', '', (string) ($p['phone'] ?? ''));
    $address = trim((string) ($p['address'] ?? ''));
    $area = (string) ($p['area'] ?? '');
    $service = (string) ($p['service'] ?? '');
    $issue = trim((string) ($p['issue'] ?? ''));
    $priority = (string) ($p['priority'] ?? '');

    if (strlen($name) < 2) {
        $err[] = 'Please enter your full name.';
    }
    if (strlen($phone) !== 10) {
        $err[] = 'Enter a valid 10-digit mobile number.';
    }
    if (strlen($address) < 5) {
        $err[] = 'Please enter a complete address.';
    }
    if (!isset($areas[$area])) {
        $err[] = 'Please select a valid area.';
    }
    if (!isset($services[$service])) {
        $err[] = 'Please choose a valid service.';
    }
    if (strlen($issue) < 10) {
        $err[] = 'Describe the problem in at least 10 characters.';
    }
    if (!isset($priorities[$priority])) {
        $err[] = 'Please select priority.';
    }

    return [$err, compact('name', 'phone', 'address', 'area', 'service', 'issue', 'priority')];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $data] = validate_booking($_POST, $services, $areas, $priorities);
    $old = [
        'name'     => $data['name'],
        'phone'    => $data['phone'],
        'address'  => $data['address'],
        'area'     => $data['area'],
        'service'  => $data['service'],
        'issue'    => $data['issue'],
        'priority' => $data['priority'],
    ];

    if ($errors === []) {
        $conn = db();
        $sql = 'INSERT INTO bookings (name, phone, address, area, service, issue, priority, status, price)
                VALUES (?, ?, ?, ?, ?, ?, ?, "pending", 0.00)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sssssss',
            $data['name'],
            $data['phone'],
            $data['address'],
            $data['area'],
            $data['service'],
            $data['issue'],
            $data['priority']
        );
        $stmt->execute();
        $stmt->close();

        $svcLabel = label_service($data['service']);
        $areaLabel = label_area($data['area']);
        $msg = sprintf(
            'New Booking: %s - %s - %s',
            $svcLabel,
            $data['issue'],
            $areaLabel
        );
        $wa = 'https://wa.me/' . WHATSAPP_BUSINESS_NUMBER . '?text=' . rawurlencode($msg);
        header('Location: ' . $wa, true, 302);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book a service — Dispatch</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
  <div class="page-wrap">
    <header class="site-header">
      <div class="container inner">
        <a class="logo" href="index.php">Dispatch Home</a>
        <nav class="nav-links">
          <a href="index.php">Services</a>
          <a href="book.php">Book</a>
          <a href="status.php">Track</a>
        </nav>
      </div>
    </header>

    <main>
      <div class="container">
        <h1>Book a service</h1>
        <p class="lead">We’ll open WhatsApp with your booking summary for quick confirmation.</p>

        <div class="card-panel">
          <?php if ($errors !== []): ?>
            <div class="alert alert-error" role="alert">
              <?php foreach ($errors as $e): ?>
                <div><?php echo e($e); ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form id="booking-form" method="post" action="book.php" novalidate>
            <div class="form-group">
              <label for="name">Name</label>
              <input type="text" id="name" name="name" required maxlength="255" value="<?php echo e($old['name']); ?>">
            </div>
            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="tel" id="phone" name="phone" required inputmode="numeric" maxlength="10" placeholder="10-digit mobile" value="<?php echo e($old['phone']); ?>">
            </div>
            <div class="form-group">
              <label for="address">Address</label>
              <textarea id="address" name="address" required><?php echo e($old['address']); ?></textarea>
            </div>
            <div class="form-group">
              <label for="area">Area</label>
              <select id="area" name="area" required>
                <?php foreach ($areas as $val => $label): ?>
                  <option value="<?php echo e($val); ?>" <?php echo $old['area'] === $val ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="service">Service type</label>
              <select id="service" name="service" required>
                <?php foreach ($services as $val => $label): ?>
                  <option value="<?php echo e($val); ?>" <?php echo $old['service'] === $val ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="issue">Problem description</label>
              <textarea id="issue" name="issue" required placeholder="Describe the issue..."><?php echo e($old['issue']); ?></textarea>
            </div>
            <div class="form-group">
              <label for="priority">Priority</label>
              <select id="priority" name="priority" required>
                <?php foreach ($priorities as $val => $label): ?>
                  <option value="<?php echo e($val); ?>" <?php echo $old['priority'] === $val ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit &amp; open WhatsApp</button>
          </form>
        </div>
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Home Services Dispatch System</p>
      </div>
    </footer>
  </div>
  <script src="../assets/script.js" defer></script>
</body>
</html>

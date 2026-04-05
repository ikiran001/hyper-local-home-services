<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/** Allowed service keys (must match DB / admin filters). */
const SERVICE_OPTIONS = [
    'electrician' => 'Electrician',
    'ac_repair'     => 'AC Repair',
    'plumber'       => 'Plumber',
];

$errors = [];
$old = [
    'name'    => '',
    'phone'   => '',
    'address' => '',
    'service' => 'electrician',
    'issue'   => '',
];

// Pre-select from query string (?service=...)
if (isset($_GET['service']) && is_string($_GET['service'])) {
    $k = $_GET['service'];
    if (isset(SERVICE_OPTIONS[$k])) {
        $old['service'] = $k;
    }
}

/**
 * Validate booking form (server-side).
 */
function validate_booking(array $p): array
{
    $err = [];
    $name = trim((string) ($p['name'] ?? ''));
    $phone = preg_replace('/\D/', '', (string) ($p['phone'] ?? ''));
    $address = trim((string) ($p['address'] ?? ''));
    $service = (string) ($p['service'] ?? '');
    $issue = trim((string) ($p['issue'] ?? ''));

    if (strlen($name) < 2) {
        $err[] = 'Please enter your full name.';
    }
    if (strlen($phone) !== 10) {
        $err[] = 'Please enter a valid 10-digit mobile number.';
    }
    if (strlen($address) < 5) {
        $err[] = 'Please enter a complete address.';
    }
    if (!isset(SERVICE_OPTIONS[$service])) {
        $err[] = 'Please choose a valid service.';
    }
    if (strlen($issue) < 10) {
        $err[] = 'Please describe the problem in at least 10 characters.';
    }

    return [$err, compact('name', 'phone', 'address', 'service', 'issue')];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $data] = validate_booking($_POST);
    $old = [
        'name'    => $data['name'],
        'phone'   => $data['phone'],
        'address' => $data['address'],
        'service' => $data['service'],
        'issue'   => $data['issue'],
    ];

    if ($errors === []) {
        $conn = db();
        $sql = 'INSERT INTO bookings (name, phone, address, service, issue, status) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $conn->prepare($sql);
        $status = 'pending';
        $stmt->execute([
            $data['name'],
            $data['phone'],
            $data['address'],
            $data['service'],
            $data['issue'],
            $status,
        ]);

        // WhatsApp redirect — message format per spec
        $serviceLabel = SERVICE_OPTIONS[$data['service']];
        $msg = sprintf(
            'New Booking: %s - %s - %s',
            $serviceLabel,
            $data['issue'],
            $data['address']
        );
        $waDigits = '91' . preg_replace('/\D/', '', WHATSAPP_BUSINESS_NUMBER);
        $url = 'https://wa.me/' . $waDigits . '?text=' . rawurlencode($msg);
        header('Location: ' . $url, true, 302);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book a service — Home Services</title>
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
        <h1>Book a service</h1>
        <p class="lead">Fill the form — we’ll open WhatsApp with your booking summary.</p>

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
              <input type="tel" id="phone" name="phone" required inputmode="numeric" autocomplete="tel" maxlength="10" placeholder="10-digit mobile" value="<?php echo e($old['phone']); ?>">
            </div>
            <div class="form-group">
              <label for="address">Address</label>
              <textarea id="address" name="address" required><?php echo e($old['address']); ?></textarea>
            </div>
            <div class="form-group">
              <label for="service">Service type</label>
              <select id="service" name="service" required>
                <?php foreach (SERVICE_OPTIONS as $val => $label): ?>
                  <option value="<?php echo e($val); ?>" <?php echo $old['service'] === $val ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="issue">Problem description</label>
              <textarea id="issue" name="issue" required placeholder="Describe the issue..."><?php echo e($old['issue']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit &amp; open WhatsApp</button>
          </form>
        </div>
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

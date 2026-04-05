<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$conn = db();

$total = (int) $conn->query('SELECT COUNT(*) AS c FROM bookings')->fetch_assoc()['c'];
$pending = (int) $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'pending'")->fetch_assoc()['c'];
$assigned = (int) $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'assigned'")->fetch_assoc()['c'];
$completed = (int) $conn->query("SELECT COUNT(*) AS c FROM bookings WHERE status = 'completed'")->fetch_assoc()['c'];

$qEarn = "SELECT COALESCE(SUM(price), 0) AS e FROM bookings
          WHERE status = 'completed' AND completed_at IS NOT NULL AND DATE(completed_at) = CURDATE()";
$earningsToday = (float) $conn->query($qEarn)->fetch_assoc()['e'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Admin</title>
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
          <h1>Dashboard</h1>
        </div>

        <div class="stats-grid stats-grid-5">
          <div class="stat-card">
            <div class="num"><?php echo $total; ?></div>
            <div class="label">Total bookings</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $pending; ?></div>
            <div class="label">Pending</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $assigned; ?></div>
            <div class="label">Assigned</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $completed; ?></div>
            <div class="label">Completed</div>
          </div>
          <div class="stat-card stat-earnings">
            <div class="num">₹<?php echo number_format($earningsToday, 2); ?></div>
            <div class="label">Today’s earnings</div>
          </div>
        </div>

        <p><a class="btn btn-primary" href="bookings.php">Manage bookings</a></p>
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <p>Home Services Dispatch System — Admin</p>
      </div>
    </footer>
  </div>
</body>
</html>

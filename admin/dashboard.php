<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';

$conn = db();

$total = (int) $conn->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$pending = (int) $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$completed = (int) $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin dashboard — Home Services</title>
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

        <div class="stats-grid">
          <div class="stat-card">
            <div class="num"><?php echo $total; ?></div>
            <div class="label">Total bookings</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $pending; ?></div>
            <div class="label">Pending</div>
          </div>
          <div class="stat-card">
            <div class="num"><?php echo $completed; ?></div>
            <div class="label">Completed</div>
          </div>
        </div>

        <p><a class="btn btn-primary" href="bookings.php">Manage bookings</a></p>
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <p>Admin panel — Home Services Booking Platform</p>
      </div>
    </footer>
  </div>
</body>
</html>

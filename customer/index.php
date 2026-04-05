<?php
declare(strict_types=1);
// Customer homepage — service cards and entry to booking flow
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home Services Booking — Book trusted pros near you</title>
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
        <section class="hero">
          <h1>Home Services Booking Platform</h1>
          <p class="lead">Fast, reliable electricians, AC repair, and plumbing — book in minutes.</p>
        </section>

        <div class="services-grid">
          <article class="service-card">
            <div class="service-icon" aria-hidden="true">⚡</div>
            <h3>Electrician</h3>
            <p>Wiring, switches, fans, MCB trips, and safe electrical fixes at your doorstep.</p>
            <a class="btn btn-primary btn-block" href="book.php?service=electrician">Book Now</a>
          </article>

          <article class="service-card">
            <div class="service-icon" aria-hidden="true">❄️</div>
            <h3>AC Repair</h3>
            <p>Gas refill, cooling issues, servicing, and installation support for split &amp; window ACs.</p>
            <a class="btn btn-primary btn-block" href="book.php?service=ac_repair">Book Now</a>
          </article>

          <article class="service-card">
            <div class="service-icon" aria-hidden="true">🔧</div>
            <h3>Plumber</h3>
            <p>Leaks, taps, drainage, motor, and bathroom fittings — quick response in your area.</p>
            <a class="btn btn-primary btn-block" href="book.php?service=plumber">Book Now</a>
          </article>
        </div>
      </div>
    </main>

    <footer class="site-footer">
      <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Home Services Booking Platform</p>
      </div>
    </footer>
  </div>
</body>
</html>

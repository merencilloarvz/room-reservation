<?php
// name of the current file, used to highlight the menu
$page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reserve - Study Hub Bookings</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<?php // top menu ?>
<nav class="navbar navbar-expand-md app-nav">
  <div class="container">
    <a class="brand" href="index.php"><span class="brand-logo"><i class="bi bi-building"></i></span> Reserve</a>
    <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto ms-md-3">
        <li class="nav-item"><a class="nav-link <?= $page == 'index.php' ? 'active' : '' ?>" href="index.php">Rooms</a></li>
        <li class="nav-item"><a class="nav-link <?= $page == 'book.php' ? 'active' : '' ?>" href="book.php">Book a room</a></li>
        <li class="nav-item"><a class="nav-link <?= $page == 'check_booking.php' ? 'active' : '' ?>" href="check_booking.php">Check booking</a></li>
      </ul>
      <?php // if the admin is already logged in, show a dashboard button instead of login ?>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a class="btn btn-outline-secondary btn-sm" href="admin/dashboard.php">Admin dashboard</a>
      <?php else: ?>
        <a class="btn btn-outline-secondary btn-sm" href="login.php">Admin login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<div class="container pb-4">

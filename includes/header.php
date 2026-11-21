<?php
// admin header: runs the login check first, then prints the menu + start of the html
require_once __DIR__ . '/auth.php';

// name of the current file (ex. rooms.php), used to highlight the active menu item
$page = basename($_SERVER['PHP_SELF']);

// if the current page is one of the given files, return "active"
// so that menu link gets the highlighted style
function nav_active($files) { global $page; return in_array($page, $files) ? 'active' : ''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reserve - Admin</title>
  <?php // style.css is loaded LAST on purpose so it can override bootstrap's styles ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>
<?php // top menu ?>
<nav class="navbar navbar-expand-md app-nav">
  <div class="container">
    <a class="brand" href="dashboard.php"><span class="brand-logo"><i class="bi bi-building"></i></span> Reserve</a>
    <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto ms-md-3">
        <li class="nav-item"><a class="nav-link <?= nav_active(['dashboard.php']) ?>" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link <?= nav_active(['rooms.php', 'room_form.php']) ?>" href="rooms.php">Rooms</a></li>
        <li class="nav-item"><a class="nav-link <?= nav_active(['room_types.php']) ?>" href="room_types.php">Room Types</a></li>
        <li class="nav-item"><a class="nav-link <?= nav_active(['reservations.php', 'reservation_form.php']) ?>" href="reservations.php">Reservations</a></li>
      </ul>
      <div class="d-flex align-items-center gap-3">
        <a href="../index.php" class="link-quiet"><i class="bi bi-box-arrow-up-right"></i> View public site</a>
        <span class="d-flex align-items-center gap-2 small"><span class="avatar"><?= e(initials($_SESSION['full_name'])) ?></span> <?= e($_SESSION['full_name']) ?></span>
        <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Log out</a>
      </div>
    </div>
  </div>
</nav>
<div class="container pb-4">
<?php // print the one-time message from flash() if there is one. unset() = it only shows once ?>
<?php if (!empty($_SESSION['flash'])): $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show">
    <?= e($f['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

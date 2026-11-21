<?php
// admin login page
session_start(); // the session is what remembers who is logged in between pages
require_once __DIR__ . '/includes/db_connect.php';

// already logged in? no point seeing the login form again, straight to the dashboard
if (!empty($_SESSION['user_id'])) { header('Location: admin/dashboard.php'); exit; }

// message to show if the login fails
$error = '';
// runs only when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // get what the user typed
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    // look for the username. the ? placeholder + bind_param = the typed value never
    // becomes part of the sql string itself, so sql injection won't work here
    $stmt = $conn->prepare("SELECT id, password, full_name FROM users WHERE username = ?");
    $stmt->bind_param("s", $username); // "s" = the value is a string
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // password_verify checks the typed password against the HASH stored in the db.
    // we never store or see the real password, only the hash.
    // $user is checked first so it doesn't crash when the username doesn't exist
    if ($user && password_verify($password, $user['password'])) {
        // fresh session id on every login = protects against session fixation
        session_regenerate_id(true);
        // once user_id is in the session, every admin page treats us as logged in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        header('Location: admin/dashboard.php');
        exit;
    }
    // one same error for wrong username OR wrong password,
    // so nobody can figure out which accounts exist
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin login - Reserve</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body class="login-wrap">
<div class="login-box">
  <?php // dark panel on the left ?>
  <div class="login-side">
    <div class="brand-logo"><i class="bi bi-building"></i></div>
    <h2>Reserve</h2>
    <p>Manage study rooms, meeting rooms and hackathon spaces in one place.</p>
  </div>
  <div class="login-form">
    <h4 class="fw-bold mb-1">Welcome back</h4>
    <p class="text-muted mb-4">Sign in to manage reservations</p>
    <?php // show the error message if there is one ?>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <div class="mb-3"><label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus></div>
      <div class="mb-4"><label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary w-100 py-2">Log in</button>
    </form>
    <p class="text-center small mb-0 mt-3"><a href="index.php" class="link-quiet">&larr; Back to rooms</a></p>
  </div>
</div>
</body>
</html>

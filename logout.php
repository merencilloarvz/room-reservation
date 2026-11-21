<?php
session_start(); // needed so the session can be destroyed
session_destroy(); // wipe all session data = logged out
header('Location: login.php'); // back to the login page
exit;

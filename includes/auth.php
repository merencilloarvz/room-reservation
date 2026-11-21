<?php
// included at the top of every admin page.
// its whole job: make sure the visitor is logged in, otherwise kick to login.php
session_start(); // start/resume the session so $_SESSION works
require_once __DIR__ . '/db_connect.php'; // db connection ($conn), db_connect lives in this same folder
require_once __DIR__ . '/functions.php'; // helper functions

// no user_id in the session = not logged in. redirect and stop the page right here.
// the admin pages live in the admin/ folder, so go up one level to reach login.php
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

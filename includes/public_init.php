<?php
// same idea as auth.php but WITHOUT the login check,
// the public pages are open to everyone
session_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

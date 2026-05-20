<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
$_SESSION = [];
session_destroy();
header('Location: ' . url('login.php'));
exit;

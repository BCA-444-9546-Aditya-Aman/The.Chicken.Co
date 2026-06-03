<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
start_admin_session();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;

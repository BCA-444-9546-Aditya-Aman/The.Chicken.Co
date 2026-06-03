<?php
/**
 * POST api/auth/logout.php
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
start_customer_session();
$_SESSION = [];
session_destroy();

json_response(true, 'Logged out successfully.');

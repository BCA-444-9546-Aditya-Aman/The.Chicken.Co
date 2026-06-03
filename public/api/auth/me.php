<?php
/**
 * GET api/auth/me.php
 * Returns current logged-in user info (used on page load to check session).
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
require_method('GET');
start_customer_session();

$user = get_logged_in_user();
if (!$user) {
    json_response(false, 'Not authenticated.', [], 401);
}

json_response(true, '', ['user' => $user]);

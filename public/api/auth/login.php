<?php
/**
 * POST api/auth/login.php
 * Body: { "email": "...", "password": "..." }
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
require_method('POST');
start_customer_session();

$body = get_json_body();
$email    = sanitize($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!$email || !$password) {
    json_response(false, 'Email and password are required.', [], 422);
}

$db = get_db();
$stmt = $db->prepare("SELECT id, full_name, email, phone, address, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    json_response(false, 'Invalid email or password.', [], 401);
}

// Store user in session (exclude password)
unset($user['password']);
$_SESSION['user'] = $user;

json_response(true, 'Login successful.', ['user' => $user]);

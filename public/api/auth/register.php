<?php
/**
 * POST api/auth/register.php
 * Body: { "full_name": "...", "email": "...", "phone": "...", "password": "..." }
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
require_method('POST');
start_customer_session();

$body     = get_json_body();
$name     = sanitize($body['full_name'] ?? '');
$email    = sanitize($body['email'] ?? '');
$phone    = sanitize($body['phone'] ?? '');
$password = $body['password'] ?? '';

if (!$name || !$email || !$phone || !$password) {
    json_response(false, 'All fields are required.', [], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Invalid email address.', [], 422);
}
if (strlen($password) < 6) {
    json_response(false, 'Password must be at least 6 characters.', [], 422);
}

$db = get_db();

// Check if email already exists
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(false, 'An account with this email already exists.', [], 409);
}

$hashed = password_hash($password, PASSWORD_BCRYPT);
$stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, $email, $phone, $hashed]);
$userId = $db->lastInsertId();

$user = ['id' => $userId, 'full_name' => $name, 'email' => $email, 'phone' => $phone, 'address' => null];
$_SESSION['user'] = $user;

json_response(true, 'Account created successfully.', ['user' => $user], 201);

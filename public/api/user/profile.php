<?php
/**
 * GET  api/user/profile.php  — fetch profile
 * POST api/user/profile.php  — update profile
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
start_customer_session();
$currentUser = require_auth();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT id, full_name, email, phone, address FROM users WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $user = $stmt->fetch();
    json_response(true, '', ['user' => $user]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body    = get_json_body();
    $name    = sanitize($body['full_name'] ?? '');
    $phone   = sanitize($body['phone'] ?? '');
    $address = sanitize($body['address'] ?? '');

    if (!$name || !$phone) {
        json_response(false, 'Name and phone are required.', [], 422);
    }
    if (strlen($name) < 3 || !preg_match("/^[a-zA-Z\s]+$/", $name)) {
        json_response(false, 'Name must be at least 3 characters and contain letters/spaces only.', [], 422);
    }
    if (preg_replace('/[^0-9]/', '', $phone) !== $phone || strlen($phone) !== 10) {
        json_response(false, 'Phone number must be exactly 10 digits.', [], 422);
    }

    $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $address, $currentUser['id']]);

    // Update session too
    $_SESSION['user']['full_name'] = $name;
    $_SESSION['user']['phone']     = $phone;
    $_SESSION['user']['address']   = $address;

    json_response(true, 'Profile updated successfully.');
}

json_response(false, 'Method not allowed.', [], 405);

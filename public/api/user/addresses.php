<?php
/**
 * GET  api/user/addresses.php  — fetch saved addresses
 * POST api/user/addresses.php  — manage addresses (add, delete, set_default)
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
start_customer_session();
$currentUser = require_auth();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $stmt->execute([$currentUser['id']]);
    $addresses = $stmt->fetchAll();
    json_response(true, '', ['addresses' => $addresses]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body   = get_json_body();
    $action = sanitize($body['action'] ?? '');

    if ($action === 'add') {
        $address_line = sanitize($body['address_line'] ?? '');
        $city         = sanitize($body['city'] ?? '');
        $pin_code     = sanitize($body['pin_code'] ?? '');
        $notes        = sanitize($body['delivery_notes'] ?? '');

        if (!$address_line || !$city || !$pin_code) {
            json_response(false, 'Address, city, and PIN code are required.', [], 422);
        }
        if (strlen($address_line) < 5) {
            json_response(false, 'Street address must be at least 5 characters.', [], 422);
        }
        if (strlen($city) < 2) {
            json_response(false, 'City must be at least 2 characters.', [], 422);
        }
        if (preg_replace('/[^0-9]/', '', $pin_code) !== $pin_code || strlen($pin_code) !== 6) {
            json_response(false, 'PIN code must be exactly 6 digits.', [], 422);
        }

        // Check if user has any addresses yet
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM user_addresses WHERE user_id = ?");
        $checkStmt->execute([$currentUser['id']]);
        $count = $checkStmt->fetch()['count'];
        $is_default = ($count == 0) ? 1 : 0;

        $stmt = $db->prepare("
            INSERT INTO user_addresses (user_id, address_line, city, pin_code, delivery_notes, is_default)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$currentUser['id'], $address_line, $city, $pin_code, $notes, $is_default]);
        
        json_response(true, 'Address added successfully.', ['address_id' => $db->lastInsertId()]);
    }

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            json_response(false, 'Address ID is required.', [], 422);
        }

        // Verify ownership and check if it was default
        $checkStmt = $db->prepare("SELECT is_default FROM user_addresses WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$id, $currentUser['id']]);
        $address = $checkStmt->fetch();

        if (!$address) {
            json_response(false, 'Address not found or unauthorized.', [], 404);
        }

        $was_default = $address['is_default'];

        $stmt = $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $currentUser['id']]);

        // If we deleted the default address, set the next one as default
        if ($was_default) {
            $nextStmt = $db->prepare("SELECT id FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $nextStmt->execute([$currentUser['id']]);
            $next = $nextStmt->fetch();
            if ($next) {
                $updateStmt = $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?");
                $updateStmt->execute([$next['id']]);
            }
        }

        json_response(true, 'Address deleted successfully.');
    }

    if ($action === 'set_default') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            json_response(false, 'Address ID is required.', [], 422);
        }

        // Verify ownership
        $checkStmt = $db->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$id, $currentUser['id']]);
        if (!$checkStmt->fetch()) {
            json_response(false, 'Address not found or unauthorized.', [], 404);
        }

        // Reset all defaults for this user
        $resetStmt = $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
        $resetStmt->execute([$currentUser['id']]);

        // Set new default
        $setDefaultStmt = $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ?");
        $setDefaultStmt->execute([$id]);

        json_response(true, 'Default address updated successfully.');
    }

    json_response(false, 'Invalid action specified.', [], 422);
}

json_response(false, 'Method not allowed.', [], 405);

<?php
/**
 * POST api/orders/cancel.php
 * Body: { order_number: "ORD-XXXX" }
 * Cancels a live order for the logged-in user.
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
require_method('POST');
start_customer_session();
$currentUser = require_auth();

$body = get_json_body();
$orderNumber = sanitize($body['order_number'] ?? '');

if (empty($orderNumber)) {
    json_response(false, 'Order number is required.', [], 400);
}

$db = get_db();

// Verify the order exists, belongs to the current user, and is cancellable
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? AND order_number = ?");
$stmt->execute([$currentUser['id'], $orderNumber]);
$order = $stmt->fetch();

if (!$order) {
    json_response(false, 'Order not found.', [], 404);
}

if ($order['status'] === 'delivered') {
    json_response(false, 'Order has already been delivered and cannot be cancelled.', [], 422);
}

if ($order['status'] === 'cancelled') {
    json_response(false, 'Order is already cancelled.', [], 422);
}

// Update the order status to cancelled
$updateStmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
$updateStmt->execute([$order['id']]);

json_response(true, 'Order cancelled successfully.');

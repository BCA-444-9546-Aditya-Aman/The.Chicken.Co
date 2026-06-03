<?php
/**
 * GET api/orders/history.php
 * Returns all past orders for the logged-in user, with items.
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
require_method('GET');
start_customer_session();
$currentUser = require_auth();

$db = get_db();

$orderNumber = sanitize($_GET['order_number'] ?? '');

if ($orderNumber) {
    // Fetch details for a specific order
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? AND order_number = ?");
    $stmt->execute([$currentUser['id'], $orderNumber]);
    $order = $stmt->fetch();

    if ($order) {
        $iStmt = $db->prepare("SELECT oi.*, mi.image FROM order_items oi LEFT JOIN menu_items mi ON oi.item_id = mi.id WHERE oi.order_id = ?");
        $iStmt->execute([$order['id']]);
        $order['items'] = $iStmt->fetchAll();

        // Calculate remaining seconds of 8-minute promise timezone-safely
        $createdAtTime = strtotime($order['created_at']);
        $elapsed = time() - $createdAtTime;
        $promiseSeconds = 8 * 60;
        $remaining = $promiseSeconds - $elapsed;
        $order['remaining_seconds'] = $remaining > 0 ? $remaining : 0;

        json_response(true, '', ['order' => $order]);
    } else {
        json_response(false, 'Order not found.', [], 404);
    }
} else {
    // Fetch list of all orders
    $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$currentUser['id']]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $iStmt = $db->prepare("SELECT oi.*, mi.image FROM order_items oi LEFT JOIN menu_items mi ON oi.item_id = mi.id WHERE oi.order_id = ?");
        $iStmt->execute([$order['id']]);
        $order['items'] = $iStmt->fetchAll();
    }

    json_response(true, '', ['orders' => $orders]);
}

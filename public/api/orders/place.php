<?php
/**
 * POST api/orders/place.php
 * Body: { cart: [...], contact: {...}, delivery: {...}, payment_method: "card"|"cod", coupon_code: "" }
 */
require_once __DIR__ . '/../../../includes/helpers.php';
require_once __DIR__ . '/../../../includes/auth_check.php';

set_cors();
require_method('POST');
start_customer_session();
$currentUser = require_auth();

$body = get_json_body();
$cart           = $body['cart']           ?? [];
$contact        = $body['contact']        ?? [];
$delivery       = $body['delivery']       ?? [];
$payment_method = sanitize($body['payment_method'] ?? 'cod');
$coupon_code    = sanitize($body['coupon_code'] ?? '');

$first_name = sanitize($contact['first_name'] ?? '');
$last_name  = sanitize($contact['last_name'] ?? '');
$phone      = preg_replace('/[^0-9]/', '', sanitize($contact['phone'] ?? ''));
$email      = sanitize($contact['email'] ?? '');

$address    = sanitize($delivery['address'] ?? '');
$city       = sanitize($delivery['city'] ?? '');
$pin        = preg_replace('/[^0-9]/', '', sanitize($delivery['pin'] ?? ''));

if (strlen($first_name) < 2 || !preg_match("/^[a-zA-Z\s]+$/", $first_name)) {
    json_response(false, 'First name must be at least 2 letters.', [], 422);
}
if (strlen($last_name) < 2 || !preg_match("/^[a-zA-Z\s]+$/", $last_name)) {
    json_response(false, 'Last name must be at least 2 letters.', [], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(false, 'Invalid email address.', [], 422);
}
if (strlen($phone) !== 10) {
    json_response(false, 'Phone number must be exactly 10 digits.', [], 422);
}
if (strlen($address) < 5) {
    json_response(false, 'Street address must be at least 5 characters.', [], 422);
}
if (strlen($city) < 2) {
    json_response(false, 'City must be at least 2 characters.', [], 422);
}
if (strlen($pin) !== 6) {
    json_response(false, 'PIN code must be exactly 6 digits.', [], 422);
}

if (empty($cart)) {
    json_response(false, 'Your cart is empty.', [], 422);
}

$db = get_db();

// ── Calculate totals ─────────────────────────────────────────
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
}
$tax      = round($subtotal * 0.05, 2);
$delivery_fee = $subtotal > 0 ? 40 : 0;
$discount = 0;

// ── Validate coupon ───────────────────────────────────────────
if ($coupon_code) {
    $stmt = $db->prepare("SELECT * FROM offers WHERE code = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE()");
    $stmt->execute([$coupon_code]);
    $offer = $stmt->fetch();

    if ($offer && $subtotal >= $offer['min_order_value']) {
        if ($offer['discount_type'] === 'percent') {
            $discount = round($subtotal * $offer['discount_value'] / 100, 2);
        } else {
            $discount = min((float)$offer['discount_value'], $subtotal);
        }
    }
}

$total = $subtotal + $tax + $delivery_fee - $discount;

// ── Insert order ──────────────────────────────────────────────
$order_number = generate_order_number();
$stmt = $db->prepare("
    INSERT INTO orders
        (user_id, order_number, payment_method, delivery_name, delivery_phone,
         delivery_email, delivery_address, delivery_city, delivery_pin,
         delivery_notes, subtotal, tax, delivery_fee, discount, total, coupon_code)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $currentUser['id'],
    $order_number,
    $payment_method,
    sanitize($contact['first_name'] . ' ' . $contact['last_name']),
    sanitize($contact['phone'] ?? ''),
    sanitize($contact['email'] ?? ''),
    sanitize($delivery['address'] ?? ''),
    sanitize($delivery['city'] ?? ''),
    sanitize($delivery['pin'] ?? ''),
    sanitize($delivery['notes'] ?? ''),
    $subtotal,
    $tax,
    $delivery_fee,
    $discount,
    $total,
    $coupon_code ?: null,
]);

$order_id = $db->lastInsertId();

// ── Insert order items ────────────────────────────────────────
$itemStmt = $db->prepare("
    INSERT INTO order_items (order_id, item_id, portion_name, item_name, unit_price, quantity, line_total)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

foreach ($cart as $item) {
    // item id format from cart: "item-wings-half" → try to match menu item
    $parts     = explode('-', $item['id'] ?? '');
    $portion   = end($parts);
    $item_name = sanitize($item['name'] ?? 'Unknown Item');
    $price     = (float)($item['price'] ?? 0);
    $qty       = (int)($item['quantity'] ?? 1);

    // Try to find the item_id in menu_items by matching name
    $mStmt = $db->prepare("SELECT id FROM menu_items WHERE name LIKE ? LIMIT 1");
    $baseName = preg_replace('/\s*\(.*\)/', '', $item_name); // strip "(Half Plate)" suffix
    $mStmt->execute(['%' . trim($baseName) . '%']);
    $menuItem = $mStmt->fetch();
    $menu_item_id = $menuItem ? $menuItem['id'] : null;

    // Extract portion name from item name e.g. "Classic Crispy Wings (Half Plate)"
    preg_match('/\(([^)]+)\)/', $item_name, $matches);
    $portion_name = $matches[1] ?? 'Standard';

    $itemStmt->execute([$order_id, $menu_item_id, $portion_name, $item_name, $price, $qty, $price * $qty]);
}

// ── Mark payment status ───────────────────────────────────────
if ($payment_method === 'cod') {
    $db->prepare("UPDATE orders SET status = 'confirmed', payment_status = 'pending' WHERE id = ?")->execute([$order_id]);
} else {
    // Online: in real world you'd run a payment gateway here
    $db->prepare("UPDATE orders SET status = 'confirmed', payment_status = 'paid' WHERE id = ?")->execute([$order_id]);
}

json_response(true, 'Order placed successfully.', [
    'order_number' => $order_number,
    'order_id'     => $order_id,
    'total'        => $total,
], 201);

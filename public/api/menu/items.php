<?php
/**
 * GET api/menu/items.php
 * Returns all available menu items with their portion options.
 */
require_once __DIR__ . '/../../../includes/helpers.php';

set_cors();
require_method('GET');
$db = get_db();

$items = $db->query("SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category, id")->fetchAll();

foreach ($items as &$item) {
    $stmt = $db->prepare("SELECT id, portion_name, price, is_default FROM item_portions WHERE item_id = ? ORDER BY price ASC");
    $stmt->execute([$item['id']]);
    $item['portions'] = $stmt->fetchAll();
}

json_response(true, '', ['items' => $items]);

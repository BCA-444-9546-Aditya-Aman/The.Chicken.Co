<?php
/**
 * GET api/offers/index.php
 * Returns all active and currently-valid offers.
 */
require_once __DIR__ . '/../../../includes/helpers.php';

set_cors();
require_method('GET');
$db = get_db();

$stmt = $db->query("SELECT * FROM offers WHERE is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE() ORDER BY id");
$offers = $stmt->fetchAll();

json_response(true, '', ['offers' => $offers]);

<?php
/**
 * Helper functions — The Chicken Co.
 */

require_once __DIR__ . '/config.php';

// ── JSON Response ──────────────────────────────────────────────
function json_response(bool $success, string $message = '', array $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ── CORS Headers (same-origin only) ───────────────────────────
function set_cors(): void {
    header('Access-Control-Allow-Origin: ' . BASE_URL);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// ── Request Method Guard ───────────────────────────────────────
function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        json_response(false, 'Method not allowed.', [], 405);
    }
}

// ── Sanitize Input ─────────────────────────────────────────────
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// ── Get JSON Body ──────────────────────────────────────────────
function get_json_body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ── Generate Order Number ──────────────────────────────────────
function generate_order_number(): string {
    $date = date('Ymd');
    $db = get_db();
    $stmt = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
    $count = (int) $stmt->fetchColumn();
    return 'ORD-' . $date . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

// ── Format Currency ────────────────────────────────────────────
function format_currency(float $amount): string {
    return '₹' . number_format($amount, 2);
}

// ── Time Ago ───────────────────────────────────────────────────
function time_ago(string $datetime): string {
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->days === 0 && $diff->h === 0) return $diff->i . 'm ago';
    if ($diff->days === 0) return $diff->h . 'h ago';
    if ($diff->days < 7)  return $diff->days . 'd ago';
    return $then->format('d M Y');
}

<?php
/**
 * Auth Guards — The Chicken Co.
 * Include this file at the top of any protected page/API.
 */

require_once __DIR__ . '/helpers.php';

// ── Start customer session ─────────────────────────────────────
function start_customer_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

// ── Start admin session ────────────────────────────────────────
function start_admin_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ADMIN_SESSION_NAME);
        session_start();
    }
}

// ── Get logged-in customer (or null) ──────────────────────────
// NOTE: renamed to avoid conflict with PHP built-in get_current_user()
function get_logged_in_user(): ?array {
    start_customer_session();
    return $_SESSION['user'] ?? null;
}

// ── Require customer to be logged in (for API endpoints) ──────
function require_auth(): array {
    $user = get_logged_in_user();
    if (!$user) {
        json_response(false, 'Unauthorised. Please log in.', [], 401);
    }
    return $user;
}

// ── Require customer to be logged in (for HTML pages) ─────────
function require_auth_redirect(string $redirect = '../login.php'): array {
    $user = get_logged_in_user();
    if (!$user) {
        header('Location: ' . $redirect);
        exit;
    }
    return $user;
}

// ── Get logged-in admin (or null) ─────────────────────────────
// NOTE: renamed to avoid conflict with PHP built-in get_current_user()
function get_logged_in_admin(): ?array {
    start_admin_session();
    return $_SESSION['admin'] ?? null;
}

// ── Require admin to be logged in (for admin panel pages) ─────
function require_admin(string $redirect = 'login.php'): array {
    $admin = get_logged_in_admin();
    if (!$admin) {
        header('Location: ' . $redirect);
        exit;
    }
    return $admin;
}

// ── Require admin for API endpoints ───────────────────────────
function require_admin_api(): array {
    $admin = get_logged_in_admin();
    if (!$admin) {
        json_response(false, 'Unauthorised.', [], 401);
    }
    return $admin;
}

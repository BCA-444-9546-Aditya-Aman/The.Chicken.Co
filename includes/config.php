<?php
/**
 * Database Configuration — The Chicken Co.
 * Edit the values below to match your phpMyAdmin setup.
 */

date_default_timezone_set('Asia/Kolkata');


define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'chicken_co');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Session name (keeps customer + admin sessions separate)
define('SESSION_NAME', 'chicken_co_session');
define('ADMIN_SESSION_NAME', 'chicken_co_admin_session');

// Site base URL (no trailing slash)
define('BASE_URL', 'http://localhost/chicken-website');

// Upload directory for menu item images (now inside public/)
define('UPLOAD_DIR', __DIR__ . '/../public/assets/uploads/');
define('UPLOAD_URL', BASE_URL . '/public/assets/uploads/');

/**
 * Create and return a PDO database connection.
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            // Show real error so you can diagnose — remove in production
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed.',
                'debug'   => $e->getMessage()
            ]);
            exit;
        }
    }
    return $pdo;
}

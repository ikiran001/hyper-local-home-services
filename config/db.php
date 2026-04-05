<?php
/**
 * MySQL connection (mysqli) — XAMPP / production.
 * Prepared statements only; no raw user input in SQL.
 */
declare(strict_types=1);

require_once __DIR__ . '/constants.php';

/**
 * Use 127.0.0.1 (TCP), not "localhost", on macOS/Homebrew PHP — otherwise mysqli
 * looks for a Unix socket file and you get: mysqli_sql_exception: No such file or directory.
 * XAMPP/WAMP: 127.0.0.1 works the same as localhost for TCP.
 */
define('DB_HOST', getenv('DB_HOST') !== false && getenv('DB_HOST') !== '' ? getenv('DB_HOST') : '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'home_services_dispatch');

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

/** Business WhatsApp (India): country code + 10 digits, no + or spaces — customer redirect after booking */
define('WHATSAPP_BUSINESS_NUMBER', '917032174014');

/**
 * @return mysqli
 */
function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');

    return $conn;
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
